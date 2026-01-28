<?php
/**
 * Webhook - Receive emails from Cloudflare Email Routing
 * POST /api/webhook/receive-email.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';

// Setup logging
$logFile = __DIR__ . '/../../storage/logs/webhook.log';

function logMessage($msg)
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $msg\n", FILE_APPEND);
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    logMessage("ERROR: Method not allowed (" . $_SERVER['REQUEST_METHOD'] . ")");
    die(json_encode(['error' => 'Method not allowed']));
}

// Verify webhook secret
$authHeader = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';

if ($authHeader !== WEBHOOK_SECRET) {
    http_response_code(401);
    logMessage("ERROR: Unauthorized - Secret mismatch");
    die(json_encode(['error' => 'Unauthorized']));
}

// Get email data from request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    logMessage("ERROR: Invalid JSON");
    die(json_encode(['error' => 'Invalid JSON']));
}

// Required fields
$to = $data['to'] ?? '';
$from = $data['from'] ?? '';
$fromName = $data['from_name'] ?? '';
$subject = $data['subject'] ?? '(No subject)';
$textBody = $data['text'] ?? '';
$htmlBody = $data['html'] ?? '';
$messageId = $data['message_id'] ?? uniqid('msg_');

logMessage("Received email for: $to from $from (ID: $messageId)");

if (empty($to) || empty($from)) {
    http_response_code(400);
    logMessage("ERROR: Missing required fields: to, from");
    die(json_encode(['error' => 'Missing required fields']));
}

try {
    $db = getDB();

    // Check for duplicates
    $stmt = $db->prepare('SELECT id FROM messages WHERE message_id = ?');
    $stmt->execute([$messageId]);
    if ($stmt->fetch()) {
        logMessage("WARN: Message already exists: $messageId");
        echo json_encode(['status' => 'ignored', 'message' => 'Message already exists']);
        exit;
    }

    // Find the email in database
    // Handle format "Name <email@domain.com>" if passed in $to, 
    // though Cloudflare usually sends clean address in 'to' field.
    // For safety, let's clean it up or assume it's clean strictly based on your previous code logic.
    // But aligning with `messages.php` schema (message_id is varchar).

    // Parse pure email just in case
    $cleanTo = $to;
    if (preg_match('/<(.+)>/', $to, $matches)) {
        $cleanTo = $matches[1];
    }
    $cleanTo = strtolower(trim($cleanTo));

    $stmt = $db->prepare('SELECT id FROM emails WHERE email = ?');
    $stmt->execute([$cleanTo]);
    $emailAccount = $stmt->fetch();

    if (!$emailAccount) {
        // Email not found - Log it but don't error, just like user wanted
        logMessage("Email not found in DB: {$cleanTo}");
        echo json_encode(['status' => 'success', 'message' => 'Email address not registered']);
        exit;
    }

    // Save message to database
    // table `messages` columns based on `database.sql`: 
    // email_id, from_email, from_name, subject, body_text, body_html, message_id, is_read, received_at
    $stmt = $db->prepare('
        INSERT INTO messages (email_id, from_email, from_name, subject, body_text, body_html, message_id, is_read, received_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)
    ');

    $stmt->execute([
        $emailAccount['id'],
        $from,
        $fromName,
        $subject,
        $textBody,
        $htmlBody,
        $messageId,
        $data['received_at'] ?? date('Y-m-d H:i:s')
    ]);

    $id = $db->lastInsertId();

    // Log success
    logMessage("Message saved: {$messageId} for {$cleanTo} (ID: $id)");

    echo json_encode(['id' => $id, 'message' => 'Message saved successfully']);

} catch (Exception $e) {
    // Log error
    logMessage("ERROR: " . $e->getMessage());
    http_response_code(500);
    // Show actual error for debugging
    echo json_encode([
        'error' => 'Internal server error',
        'details' => $e->getMessage()
    ]);
}
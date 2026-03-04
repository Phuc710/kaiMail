<?php
/**
 * Webhook - Receive emails from Cloudflare Email Routing
 * POST /api/webhook/receive-email.php
 * 
 * Handles MIME-encoded content from Cloudflare Worker
 * Decodes quoted-printable, base64, and RFC 2047 headers
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/EmailDecoder.php';

// Setup logging
$logFile = WEBHOOK_LOG_FILE;

function logMessage($msg)
{
    global $logFile;
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
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
$authHeader = (string) ($_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '');

if ($authHeader === '' || !hash_equals((string) WEBHOOK_SECRET, $authHeader)) {
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

// ===== DECODE EMAIL DATA FROM CLOUDFLARE WORKER =====
$decodedData = [
    'subject' => $subject,
    'from_name' => $fromName,
    'text' => $textBody,
    'html' => $htmlBody
];
$decodedData = EmailDecoder::processEmail($decodedData);

// Use decoded values
$subject = $decodedData['subject'];
$fromName = $decodedData['from_name'];
$textBody = $decodedData['text'];
$htmlBody = $decodedData['html'];

// Parse from_name if empty - extract from "Name <email@domain.com>" format
if (empty($fromName) && !empty($from)) {
    // Check if format is "Name <email>"
    if (preg_match('/^(.+?)\s*<(.+)>$/', $from, $matches)) {
        $fromName = trim($matches[1], '" ');
        $fromEmail = $matches[2];
    } else {
        // Just email address, no name
        $fromEmail = $from;
        $fromName = ''; // Will be empty, UI will handle fallback
    }
} else {
    // from_name provided or from is just email
    $fromEmail = $from;
    // Clean email if it has <> brackets
    if (preg_match('/<(.+)>/', $fromEmail, $matches)) {
        $fromEmail = $matches[1];
    }
}

logMessage("Received email for: $to from $from | Parsed: fromEmail=$fromEmail, fromName=$fromName (ID: $messageId)");

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
        $fromEmail, // Use parsed email
        $fromName,  // Use parsed name
        $subject,

            // Decode Quoted-Printable if detected immediately at source
        (strpos($textBody, '=20') !== false || strpos($textBody, '=3D') !== false)
        ? quoted_printable_decode($textBody)
        : $textBody,

        (strpos($htmlBody, '=20') !== false || strpos($htmlBody, '=3D') !== false)
        ? quoted_printable_decode($htmlBody)
        : $htmlBody,

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
    $response = ['error' => 'Internal server error'];
    if (EXPOSE_ERROR_DETAILS) {
        $response['details'] = $e->getMessage();
    }
    echo json_encode($response);
}

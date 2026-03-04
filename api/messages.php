<?php
// Error handler to return JSON
function handleJsonError($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno))
        return;
    http_response_code(500);
    $response = ['error' => 'Server Error'];
    if (defined('EXPOSE_ERROR_DETAILS') && EXPOSE_ERROR_DETAILS) {
        $response['details'] = "$errstr in $errfile:$errline";
    }
    echo json_encode($response);
    exit;
}
set_error_handler("handleJsonError");

// Catch Fatal Errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        http_response_code(500);
        $response = ['error' => 'Fatal Error'];
        if (defined('EXPOSE_ERROR_DETAILS') && EXPOSE_ERROR_DETAILS) {
            $response['details'] = $error['message'] . " in " . $error['file'] . ":" . $error['line'];
        }
        echo json_encode($response);
        exit;
    }
});

require_once __DIR__ . '/../includes/MessageService.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/middleware/ApiSecurity.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Set CORS headers
ApiSecurity::setCorsHeaders();
ApiSecurity::handlePreflight();
ApiSecurity::requireApiAuth();

try {
    $db = getDB();

    // Get single message by ID
    if (isset($_GET['id'])) {
        $message = MessageService::getMessage((int) $_GET['id']);
        if (!$message) {
            jsonResponse(['error' => 'Message not found'], 404);
        }
        jsonResponse($message);
    }

    // Get messages by email (User specific logic: find email_id first)
    $email = strtolower(trim($_GET['email'] ?? ''));

    if (empty($email)) {
        jsonResponse(['error' => 'Email is required'], 400);
    }

    $stmt = $db->prepare("SELECT id, is_expired FROM emails WHERE email = ?");
    $stmt->execute([$email]);
    $emailData = $stmt->fetch();

    if (!$emailData) {
        jsonResponse(['error' => 'Email not found'], 404);
    }

    if ($emailData['is_expired']) {
        jsonResponse(['error' => 'Email has expired'], 410);
    }

    // Use Service to get messages
    $messages = MessageService::getMessagesByEmailId($emailData['id']);

    // Count unread
    $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE email_id = ? AND is_read = 0");
    $stmt->execute([$emailData['id']]);
    $unreadCount = $stmt->fetchColumn();

    jsonResponse([
        'email' => $email,
        'total' => count($messages),
        'unread' => (int) $unreadCount,
        'messages' => $messages,
        'server_time' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log("Messages API error: " . $e->getMessage());
    $response = ['error' => 'Internal server error'];
    if (EXPOSE_ERROR_DETAILS) {
        $response['message'] = $e->getMessage();
    }
    jsonResponse($response, 500);
}

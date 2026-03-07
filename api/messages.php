<?php
// Error handler to return JSON
function handleJsonError($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        return;
    }

    http_response_code(500);
    $response = ['error' => 'Server Error'];
    if (defined('EXPOSE_ERROR_DETAILS') && EXPOSE_ERROR_DETAILS) {
        $response['details'] = "$errstr in $errfile:$errline";
    }

    echo json_encode($response);
    exit;
}
set_error_handler('handleJsonError');

// Catch Fatal Errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        http_response_code(500);
        $response = ['error' => 'Fatal Error'];
        if (defined('EXPOSE_ERROR_DETAILS') && EXPOSE_ERROR_DETAILS) {
            $response['details'] = $error['message'] . ' in ' . $error['file'] . ':' . $error['line'];
        }

        echo json_encode($response);
        exit;
    }
});

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/MessageService.php';
require_once __DIR__ . '/../includes/DatabaseOptimizer.php';
require_once __DIR__ . '/middleware/ApiSecurity.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

ApiSecurity::setCorsHeaders();
ApiSecurity::handlePreflight();
ApiSecurity::requireApiAuth();

$method = getMethod();

try {
    $db = getDB();
    DatabaseOptimizer::ensureCoreIndexes($db);

    if ($method === 'GET') {
        // Get single message by ID
        if (isset($_GET['id'])) {
            $message = MessageService::getMessage((int) $_GET['id']);
            if (!$message) {
                jsonResponse(['error' => 'Message not found'], 404);
            }

            jsonResponse($message);
        }

        // Get messages by email
        $email = strtolower(trim((string) ($_GET['email'] ?? '')));
        if ($email === '') {
            jsonResponse(['error' => 'Email is required'], 400);
        }
        $limit = (int) ($_GET['limit'] ?? 30);
        if ($limit < 1) {
            $limit = 30;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $stmt = $db->prepare('SELECT id, is_expired FROM emails WHERE email = ?');
        $stmt->execute([$email]);
        $emailData = $stmt->fetch();

        if (!$emailData) {
            jsonResponse(['error' => 'Email not found'], 404);
        }

        if ((int) $emailData['is_expired'] === 1) {
            jsonResponse(['error' => 'Email has expired'], 410);
        }

        $messages = MessageService::getMessagesByEmailId((int) $emailData['id'], $limit);

        $stmt = $db->prepare('SELECT COUNT(*) FROM messages WHERE email_id = ? AND is_read = 0');
        $stmt->execute([(int) $emailData['id']]);
        $unreadCount = $stmt->fetchColumn();

        jsonResponse([
            'email' => $email,
            'email_id' => (int) $emailData['id'],
            'total' => count($messages),
            'unread' => (int) $unreadCount,
            'messages' => $messages,
            'server_time' => date('Y-m-d H:i:s'),
        ]);
    }

    if ($method === 'DELETE') {
        $data = getJsonInput();
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        if ($email === '') {
            jsonResponse(['error' => 'email is required'], 400);
        }

        $stmt = $db->prepare('SELECT id FROM emails WHERE email = ?');
        $stmt->execute([$email]);
        $emailData = $stmt->fetch();

        if (!$emailData) {
            jsonResponse(['error' => 'Email not found'], 404);
        }

        $singleId = (int) ($data['id'] ?? 0);
        $ids = $data['ids'] ?? [];
        $deleteAll = (bool) ($data['delete_all'] ?? false);

        if ($singleId > 0) {
            $ids[] = $singleId;
        }

        $ids = array_values(array_filter(array_map('intval', (array) $ids), static fn($id) => $id > 0));

        if (!$deleteAll && count($ids) < 1) {
            jsonResponse(['error' => 'Provide id/ids or delete_all=true'], 400);
        }

        $deleted = MessageService::deleteMessages((int) $emailData['id'], $ids, $deleteAll);

        jsonResponse([
            'success' => true,
            'deleted' => (int) $deleted,
            'email' => $email,
        ]);
    }

    jsonResponse(['error' => 'Method not allowed'], 405);
} catch (Exception $e) {
    error_log('Messages API error: ' . $e->getMessage());
    $response = ['error' => 'Internal server error'];
    if (EXPOSE_ERROR_DETAILS) {
        $response['message'] = $e->getMessage();
    }

    jsonResponse($response, 500);
}

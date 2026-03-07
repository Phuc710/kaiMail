<?php
// Error handler to return JSON
function handleJsonError($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno))
        return;
    http_response_code(500);
    $response = ['error' => 'Lỗi máy chủ'];
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
        $response = ['error' => 'Lỗi nghiêm trọng'];
        if (defined('EXPOSE_ERROR_DETAILS') && EXPOSE_ERROR_DETAILS) {
            $response['details'] = $error['message'] . " in " . $error['file'] . ":" . $error['line'];
        }
        echo json_encode($response);
        exit;
    }
});

$servicePath = __DIR__ . '/../../includes/MessageService.php';
if (!file_exists($servicePath)) {
    http_response_code(500);
    $response = ['error' => 'Lỗi nghiêm trọng'];
    if (defined('EXPOSE_ERROR_DETAILS') && EXPOSE_ERROR_DETAILS) {
        $response['details'] = 'MessageService.php not found';
    }
    echo json_encode($response);
    exit;
}
require_once __DIR__ . '/../../config/app.php';
require_once $servicePath;
require_once __DIR__ . '/../middleware/AdminSecurity.php';

header('Content-Type: application/json; charset=utf-8');
AdminSecurity::setCorsHeaders();
AdminSecurity::handlePreflight();
AdminSecurity::requireAdminAuth();

$method = getMethod();

try {
    // =====================
    // GET - List/Get messages
    // =====================
    if ($method === 'GET') {
        // Get single message
        if (isset($_GET['id'])) {
            $message = MessageService::getMessage((int) $_GET['id']);
            if (!$message) {
                jsonResponse(['error' => 'Không tìm thấy tin nhắn'], 404);
            }
            jsonResponse($message);
        }

        // List messages for email
        $emailId = (int) ($_GET['email_id'] ?? 0);
        if (!$emailId) {
            jsonResponse(['error' => 'Thiếu email_id'], 400);
        }

        $messages = MessageService::getMessagesByEmailId($emailId);
        jsonResponse([
            'total' => count($messages),
            'messages' => $messages
        ]);
    }

    // =====================
    // DELETE - Delete message(s)
    // =====================
    elseif ($method === 'DELETE') {
        $data = getJsonInput();
        $ids = $data['ids'] ?? [];
        $emailId = (int) ($data['email_id'] ?? 0);
        $deleteAll = $data['delete_all'] ?? false;

        $deleted = MessageService::deleteMessages($emailId, $ids, $deleteAll);

        jsonResponse([
            'success' => true,
            'deleted' => $deleted
        ]);

    } else {
        jsonResponse(['error' => 'Phương thức không được hỗ trợ'], 405);
    }

} catch (PDOException $e) {
    error_log("Database Error in admin/messages.php: " . $e->getMessage());
    $response = ['error' => 'Lỗi cơ sở dữ liệu'];
    if (EXPOSE_ERROR_DETAILS) {
        $response['message'] = $e->getMessage();
    }
    jsonResponse($response, 500);
} catch (Exception $e) {
    error_log("General Error in admin/messages.php: " . $e->getMessage());
    $response = ['error' => 'Lỗi hệ thống'];
    if (EXPOSE_ERROR_DETAILS) {
        $response['message'] = $e->getMessage();
    }
    jsonResponse($response, 500);
}

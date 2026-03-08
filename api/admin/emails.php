<?php
// Error handler to return JSON
function handleJsonError($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        return;
    }

    http_response_code(500);
    $response = ['error' => 'Loi may chu'];
    if (defined('EXPOSE_ERROR_DETAILS') && EXPOSE_ERROR_DETAILS) {
        $response['details'] = "$errstr in $errfile:$errline";
    }

    echo json_encode($response);
    exit;
}
set_error_handler('handleJsonError');

// Catch fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        http_response_code(500);
        $response = ['error' => 'Loi nghiem trong'];
        if (defined('EXPOSE_ERROR_DETAILS') && EXPOSE_ERROR_DETAILS) {
            $response['details'] = $error['message'] . ' in ' . $error['file'] . ':' . $error['line'];
        }

        echo json_encode($response);
        exit;
    }
});

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/EmailService.php';
require_once __DIR__ . '/../middleware/AdminSecurity.php';

header('Content-Type: application/json; charset=utf-8');
AdminSecurity::setNoCacheHeaders();
AdminSecurity::setCorsHeaders();
AdminSecurity::handlePreflight();
AdminSecurity::requireAdminAuth();

$method = getMethod();
$db = getDB();

try {
    if ($method === 'GET') {
        $search = trim((string) ($_GET['search'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(100, max(10, (int) ($_GET['limit'] ?? 13)));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = 'e.email LIKE ?';
            $params[] = "%{$search}%";
        }

        $domain = trim((string) ($_GET['domain'] ?? ''));
        if ($domain !== '') {
            $where[] = 'e.email LIKE ?';
            $params[] = "%@{$domain}";
        }

        $onlyNoMessage = trim((string) ($_GET['no_message'] ?? ''));
        if ($onlyNoMessage === '1') {
            $where[] = 'NOT EXISTS (SELECT 1 FROM messages m WHERE m.email_id = e.id)';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) FROM emails e {$whereClause}";
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $sql = "
            SELECT e.*,
                   UNIX_TIMESTAMP(e.created_at) as created_ts,
                   (SELECT COUNT(*) FROM messages m WHERE m.email_id = e.id) as message_count,
                   (SELECT COUNT(*) FROM messages m WHERE m.email_id = e.id AND m.is_read = 0) as unread_count
            FROM emails e
            {$whereClause}
            ORDER BY e.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $emails = $stmt->fetchAll();

        jsonResponse([
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int) ceil($total / $limit),
            'emails' => $emails,
            'server_time' => date('Y-m-d H:i:s'),
        ]);
    }

    if ($method === 'POST') {
        $data = getJsonInput();
        $errors = [];
        $emails = EmailService::createEmails($data, $errors);

        jsonResponse([
            'success' => true,
            'created' => count($emails),
            'emails' => $emails,
            'errors' => $errors,
        ], 201);
    }

    if ($method === 'DELETE') {
        $data = getJsonInput();
        $ids = array_values(array_filter(array_map('intval', (array) ($data['ids'] ?? []))));

        if (count($ids) < 1) {
            jsonResponse(['error' => 'Thieu danh sach email can xoa'], 400);
        }

        $deleted = EmailService::deleteEmails($ids);

        jsonResponse([
            'success' => true,
            'deleted' => $deleted,
        ]);
    }

    jsonResponse(['error' => 'Phuong thuc khong duoc ho tro'], 405);
} catch (PDOException $e) {
    error_log('Database Error in admin/emails.php: ' . $e->getMessage());
    $response = ['error' => 'Loi co so du lieu'];
    if (defined('EXPOSE_ERROR_DETAILS') && EXPOSE_ERROR_DETAILS) {
        $response['message'] = $e->getMessage();
    }
    jsonResponse($response, 500);
} catch (Exception $e) {
    error_log('General Error in admin/emails.php: ' . $e->getMessage());
    $response = ['error' => 'Loi he thong'];
    if (defined('EXPOSE_ERROR_DETAILS') && EXPOSE_ERROR_DETAILS) {
        $response['message'] = $e->getMessage();
        $response['file'] = basename($e->getFile());
        $response['line'] = $e->getLine();
    }
    jsonResponse($response, 500);
}

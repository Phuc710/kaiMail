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
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        http_response_code(500);
        $response = ['error' => 'Lỗi nghiêm trọng'];
        if (defined('EXPOSE_ERROR_DETAILS') && EXPOSE_ERROR_DETAILS) {
            $response['details'] = $error['message'] . " in " . $error['file'] . ":" . $error['line'];
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
AdminSecurity::setCorsHeaders();
AdminSecurity::handlePreflight();
AdminSecurity::requireAdminAuth();

$method = getMethod();
$db = getDB();

try {
    // =====================
    // GET - List emails
    // =====================
    if ($method === 'GET') {
        $filter = $_GET['filter'] ?? 'active';
        $expiry = $_GET['expiry'] ?? '';
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(100, max(10, (int) ($_GET['limit'] ?? 13)));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($filter === 'active') {
            $where[] = "e.is_expired = 0";
        } elseif ($filter === 'expired') {
            $where[] = "e.is_expired = 1";
        }

        if ($expiry) {
            if ($expiry === 'no_message') {
                $where[] = "NOT EXISTS (SELECT 1 FROM messages m WHERE m.email_id = e.id)";
            } elseif ($expiry === 'expired') {
                $where[] = "e.is_expired = 1";
            } else {
                $where[] = "e.expiry_type = ?";
                $params[] = $expiry;
            }
        }

        if ($search) {
            $where[] = "e.email LIKE ?";
            $params[] = "%$search%";
        }

        $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

        // Count total with alias 'e'
        $countSql = "SELECT COUNT(*) FROM emails e $whereClause";
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Get emails with message count
        $sql = "
            SELECT e.*, 
                   UNIX_TIMESTAMP(e.created_at) as created_ts,
                   UNIX_TIMESTAMP(e.expires_at) as expires_ts,
                   COUNT(m.id) as message_count,
                   SUM(CASE WHEN m.is_read = 0 THEN 1 ELSE 0 END) as unread_count
            FROM emails e
            LEFT JOIN messages m ON e.id = m.email_id
            $whereClause
            GROUP BY e.id
            ORDER BY e.created_at DESC
            LIMIT $limit OFFSET $offset
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $emails = $stmt->fetchAll();

        jsonResponse([
            'total' => (int) $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit),
            'emails' => $emails,
            'server_time' => date('Y-m-d H:i:s')
        ]);
    }

    // =====================
    // POST - Create email(s) -> Dùng EmailService
    // =====================
    elseif ($method === 'POST') {
        $data = getJsonInput();
        $errors = [];

        // Gọi Service createEmails
        $emails = EmailService::createEmails($data, $errors);

        jsonResponse([
            'success' => true,
            'created' => count($emails),
            'emails' => $emails,
            'errors' => $errors
        ], 201);
    }

    // =====================
    // PUT - Update email
    // =====================
    elseif ($method === 'PUT') {
        $data = getJsonInput();
        $id = (int) ($data['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare("UPDATE emails SET expiry_type = 'forever', expires_at = NULL, is_expired = 0 WHERE id = ?");
            $stmt->execute([$id]);
        }
        jsonResponse(['success' => true]);
    }

    // =====================
    // DELETE - Delete email(s) -> Dùng EmailService
    // =====================
    elseif ($method === 'DELETE') {
        $data = getJsonInput();

        $ids = $data['ids'] ?? [];
        $deleteAll = $data['delete_all'] ?? false;
        $filter = $data['filter'] ?? '';

        $deleted = EmailService::deleteEmails($ids, $deleteAll, $filter);

        jsonResponse([
            'success' => true,
            'deleted' => $deleted
        ]);
    } else {
        jsonResponse(['error' => 'Phương thức không được hỗ trợ'], 405);
    }

} catch (PDOException $e) {
    // Log error
    error_log("Database Error in admin/emails.php: " . $e->getMessage());
    $response = ['error' => 'Lỗi cơ sở dữ liệu'];
    if (EXPOSE_ERROR_DETAILS) {
        $response['message'] = $e->getMessage();
    }
    jsonResponse($response, 500);
} catch (Exception $e) {
    error_log("General Error in admin/emails.php: " . $e->getMessage());
    $response = ['error' => 'Lỗi hệ thống'];
    if (EXPOSE_ERROR_DETAILS) {
        $response['message'] = $e->getMessage();
        $response['file'] = basename($e->getFile());
        $response['line'] = $e->getLine();
    }
    jsonResponse($response, 500);
}

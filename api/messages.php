<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/MessageService.php';
require_once __DIR__ . '/../includes/DatabaseOptimizer.php';
require_once __DIR__ . '/middleware/ApiSecurity.php';

header('Content-Type: application/json; charset=utf-8');
ApiSecurity::setNoCacheHeaders();
ApiSecurity::setCorsHeaders();
ApiSecurity::handlePreflight();
ApiSecurity::requireApiAuth();

final class MessagesApiController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function handle(string $method): void
    {
        if ($method === 'GET') {
            $this->handleGet();
            return;
        }

        if ($method === 'DELETE') {
            $this->handleDelete();
            return;
        }

        jsonResponse(['error' => 'Method not allowed'], 405);
    }

    private function handleGet(): void
    {
        if (isset($_GET['id'])) {
            $message = MessageService::getMessage((int) $_GET['id']);
            if (!$message) {
                jsonResponse(['error' => 'Message not found'], 404);
            }

            jsonResponse($message);
        }

        $email = strtolower(trim((string) ($_GET['email'] ?? '')));
        if ($email === '') {
            jsonResponse(['error' => 'Email is required'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Invalid email format'], 400);
        }

        $limit = (int) ($_GET['limit'] ?? 30);
        if ($limit < 1) {
            $limit = 30;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $emailData = $this->findEmail($email);
        if (!$emailData) {
            $emailData = $this->tryCreateCustomInbox($email);
        }
        if (!$emailData) {
            jsonResponse(['error' => 'Email not found'], 404);
        }

        if ((int) $emailData['is_expired'] === 1) {
            jsonResponse(['error' => 'Email has expired'], 410);
        }

        $emailId = (int) $emailData['id'];
        $messages = MessageService::getMessagesByEmailId($emailId, $limit);
        $unreadCount = $this->countUnread($emailId);

        jsonResponse([
            'email' => $email,
            'email_id' => $emailId,
            'total' => count($messages),
            'unread' => $unreadCount,
            'messages' => $messages,
            'server_time' => date('Y-m-d H:i:s'),
        ]);
    }

    private function handleDelete(): void
    {
        $data = getJsonInput();
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if ($email === '') {
            jsonResponse(['error' => 'email is required'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'invalid email format'], 400);
        }

        $emailData = $this->findEmail($email);
        if (!$emailData) {
            jsonResponse(['error' => 'Email not found'], 404);
        }

        $singleId = (int) ($data['id'] ?? 0);
        $ids = $data['ids'] ?? [];
        $deleteAll = (bool) ($data['delete_all'] ?? false);

        if ($singleId > 0) {
            $ids[] = $singleId;
        }

        $ids = array_values(array_filter(array_map('intval', (array) $ids), static fn(int $id): bool => $id > 0));
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

    private function findEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT id, is_expired FROM emails WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    private function tryCreateCustomInbox(string $email): ?array
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return null;
        }

        $domain = strtolower(trim($parts[1]));
        $stmt = $this->db->prepare('SELECT id FROM domains WHERE domain = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$domain]);
        $domainId = (int) $stmt->fetchColumn();
        if ($domainId <= 0) {
            return null;
        }

        $insertStmt = $this->db->prepare('
            INSERT INTO emails (domain_id, email, name_type, expiry_type, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ');
        try {
            $ok = $insertStmt->execute([$domainId, $email, 'custom', 'forever']);
            if (!$ok) {
                return null;
            }
        } catch (PDOException $e) {
            // Handle concurrent create attempts for the same email.
            if ((string) $e->getCode() === '23000') {
                return $this->findEmail($email);
            }
            throw $e;
        }

        return [
            'id' => (int) $this->db->lastInsertId(),
            'is_expired' => 0,
        ];
    }

    private function countUnread(int $emailId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM messages WHERE email_id = ? AND is_read = 0');
        $stmt->execute([$emailId]);
        return (int) $stmt->fetchColumn();
    }
}

try {
    $db = getDB();
    DatabaseOptimizer::ensureCoreIndexes($db);

    $controller = new MessagesApiController($db);
    $controller->handle(getMethod());
} catch (Throwable $e) {
    error_log('Messages API error: ' . $e->getMessage());
    $response = ['error' => 'Internal server error'];
    if (defined('EXPOSE_ERROR_DETAILS') && EXPOSE_ERROR_DETAILS) {
        $response['message'] = $e->getMessage();
    }
    jsonResponse($response, 500);
}

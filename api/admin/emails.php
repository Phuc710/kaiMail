<?php
/**
 * Admin Emails API
 * KaiMail - Temp Mail System
 * 
 * GET    /api/admin/emails - List all emails
 * POST   /api/admin/emails - Create new email
 * PUT    /api/admin/emails - Update email
 * DELETE /api/admin/emails - Delete email(s)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/NameGenerator.php';

header('Content-Type: application/json; charset=utf-8');

// Require admin login
Auth::requireLogin();

$method = getMethod();
$db = getDB();

try {
    // =====================
    // GET - List emails
    // =====================
    if ($method === 'GET') {
        $filter = $_GET['filter'] ?? 'active'; // active, expired, all
        $expiry = $_GET['expiry'] ?? ''; // 30days, 1year, 2years, forever
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(100, max(10, (int) ($_GET['limit'] ?? 13)));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($filter === 'active') {
            $where[] = "is_expired = 0";
        } elseif ($filter === 'expired') {
            $where[] = "is_expired = 1";
        }

        if ($expiry) {
            $where[] = "expiry_type = ?";
            $params[] = $expiry;
        }

        if ($search) {
            $where[] = "email LIKE ?";
            $params[] = "%$search%";
        }

        $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

        // Count total
        $countSql = "SELECT COUNT(*) FROM emails $whereClause";
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Get emails with message count
        $sql = "
            SELECT e.id, e.domain_id, e.email, e.name_type, e.expiry_type, 
                   e.expires_at, e.is_expired, e.created_at, e.updated_at,
                   UNIX_TIMESTAMP(e.created_at) as created_ts,
                   UNIX_TIMESTAMP(e.expires_at) as expires_ts,
                   COUNT(m.id) as message_count,
                   SUM(CASE WHEN m.is_read = 0 THEN 1 ELSE 0 END) as unread_count
            FROM emails e
            LEFT JOIN messages m ON e.id = m.email_id
            $whereClause
            GROUP BY e.id, e.domain_id, e.email, e.name_type, e.expiry_type, 
                     e.expires_at, e.is_expired, e.created_at, e.updated_at
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
            'emails' => $emails
        ]);
    }

    // =====================
    // POST - Create email(s)
    // =====================
    elseif ($method === 'POST') {
        $data = getJsonInput();

        $nameType = $data['name_type'] ?? 'vn'; // vn, en, custom
        $expiryType = $data['expiry_type'] ?? 'forever'; // 30days, 1year, 2years, forever
        $customEmail = trim($data['email'] ?? '');
        $domain = trim($data['domain'] ?? getDefaultDomain() ?? '');
        $quantity = min(max(1, (int) ($data['quantity'] ?? 1)), 10); // 1-10 emails

        // Validate domain is provided and active
        if (empty($domain)) {
            jsonResponse(['error' => 'No domain specified and no default domain available. Please add an active domain first.'], 400);
        }

        $createdEmails = [];
        $errors = [];

        for ($i = 0; $i < $quantity; $i++) {
            // Generate or use custom email
            if ($nameType === 'custom' && $customEmail) {
                // Validate custom email
                if (!preg_match('/^[a-z0-9]+$/', $customEmail)) {
                    $errors[] = 'Email can only contain lowercase letters and numbers';
                    continue;
                }
                $username = $customEmail . ($quantity > 1 ? '_' . ($i + 1) : '');
                $actualNameType = 'custom';
            } else {
                $username = NameGenerator::generateUsername($nameType);
                $actualNameType = $nameType;
            }

            $email = $username . '@' . $domain;

            // Check if exists
            $stmt = $db->prepare("SELECT id FROM emails WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email already exists: $email";
                continue;
            }

            // Get domain_id from domains table
            $stmt = $db->prepare("SELECT id FROM domains WHERE domain = ?");
            $stmt->execute([$domain]);
            $domainRow = $stmt->fetch();

            if (!$domainRow) {
                $errors[] = "Domain not found: $domain";
                continue;
            }

            $domainId = $domainRow['id'];

            // Calculate expiry date
            $expiresAt = null;
            switch ($expiryType) {
                case '30days':
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
                    break;
                case '1year':
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 year'));
                    break;
                case '2years':
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+2 years'));
                    break;
                case 'forever':
                default:
                    $expiresAt = null;
                    $expiryType = 'forever';
            }

            // Insert with domain_id
            $stmt = $db->prepare("
                INSERT INTO emails (domain_id, email, name_type, expiry_type, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$domainId, $email, $actualNameType, $expiryType, $expiresAt]);

            $createdEmails[] = [
                'id' => $db->lastInsertId(),
                'email' => $email,
                'name_type' => $actualNameType,
                'expiry_type' => $expiryType,
                'expires_at' => $expiresAt
            ];
        }

        jsonResponse([
            'success' => true,
            'created' => count($createdEmails),
            'emails' => $createdEmails,
            'errors' => $errors
        ], 201);
    }

    // =====================
    // PUT - Update email
    // =====================
    elseif ($method === 'PUT') {
        $data = getJsonInput();
        $id = (int) ($data['id'] ?? 0);

        if (!$id) {
            jsonResponse(['error' => 'Email ID required'], 400);
        }

        $expiryType = $data['expiry_type'] ?? null;

        if ($expiryType) {
            $expiresAt = null;
            switch ($expiryType) {
                case '30days':
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
                    break;
                case '1year':
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 year'));
                    break;
                case '2years':
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+2 years'));
                    break;
            }

            $stmt = $db->prepare("
                UPDATE emails 
                SET expiry_type = ?, expires_at = ?, is_expired = 0
                WHERE id = ?
            ");
            $stmt->execute([$expiryType, $expiresAt, $id]);
        }

        jsonResponse(['success' => true]);
    }

    // =====================
    // DELETE - Delete email(s)
    // =====================
    elseif ($method === 'DELETE') {
        $data = getJsonInput();

        $ids = $data['ids'] ?? [];
        $deleteAll = $data['delete_all'] ?? false;
        $filter = $data['filter'] ?? '';

        if ($deleteAll && $filter === 'expired') {
            $stmt = $db->prepare("DELETE FROM emails WHERE is_expired = 1");
            $stmt->execute();
            $deleted = $stmt->rowCount();
        } elseif (!empty($ids)) {
            // Ensure IDs are integers
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "DELETE FROM emails WHERE id IN ($placeholders)";

            $stmt = $db->prepare($sql);
            $stmt->execute($ids);
            $deleted = $stmt->rowCount();
        } else {
            jsonResponse(['error' => 'No emails specified'], 400);
        }

        jsonResponse([
            'success' => true,
            'deleted' => $deleted
        ]);
    } else {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

} catch (Exception $e) {
    error_log("Admin emails error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}

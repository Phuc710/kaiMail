<?php
/**
 * Public Email API (external use).
 *
 * POST   /api/emails.php
 * GET    /api/emails.php?email=user@example.com
 * DELETE /api/emails.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/EmailService.php';
require_once __DIR__ . '/middleware/ApiSecurity.php';

ApiSecurity::setCorsHeaders();
ApiSecurity::handlePreflight();
header('Content-Type: application/json; charset=utf-8');
ApiSecurity::requireApiAuth();

$method = getMethod();

try {
    $db = getDB();

    if ($method === 'POST') {
        $data = getJsonInput();

        $nameType = (string) ($data['name_type'] ?? 'en');
        $domain = strtolower(trim((string) ($data['domain'] ?? '')));
        $quantity = (int) ($data['quantity'] ?? ($data['count'] ?? 1));
        $customEmail = strtolower(trim((string) ($data['email'] ?? '')));

        if (!in_array($nameType, ['vn', 'en', 'custom'], true)) {
            jsonResponse(['error' => 'Invalid name_type. Must be: vn, en, custom'], 400);
        }

        if ($quantity < 1 || $quantity > 10) {
            jsonResponse(['error' => 'quantity must be between 1 and 10'], 400);
        }

        if ($nameType === 'custom' && $customEmail === '') {
            jsonResponse(['error' => 'email is required when name_type=custom'], 400);
        }

        $errors = [];
        $created = EmailService::createEmails([
            'name_type' => $nameType,
            'domain' => $domain,
            'quantity' => $quantity,
            'email' => $customEmail,
        ], $errors);

        if (empty($created)) {
            jsonResponse([
                'success' => false,
                'created' => 0,
                'errors' => $errors,
            ], 400);
        }

        jsonResponse([
            'success' => true,
            'created' => count($created),
            'emails' => $created,
            'errors' => $errors,
        ], 201);
    }

    if ($method === 'GET') {
        $email = strtolower(trim((string) ($_GET['email'] ?? '')));
        if ($email === '') {
            jsonResponse(['error' => 'email is required'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'invalid email format'], 400);
        }

        $stmt = $db->prepare("
            SELECT id, email, expiry_type, expires_at, is_expired, created_at
            FROM emails
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $emailData = $stmt->fetch();

        if (!$emailData) {
            jsonResponse([
                'exists' => false,
                'error' => 'Email not found',
            ], 404);
        }

        if (!$emailData['is_expired'] && $emailData['expires_at']) {
            if (strtotime((string) $emailData['expires_at']) < time()) {
                $db->prepare("UPDATE emails SET is_expired = 1 WHERE id = ?")->execute([$emailData['id']]);
                $emailData['is_expired'] = 1;
            }
        }

        if ((int) $emailData['is_expired'] === 1) {
            jsonResponse([
                'exists' => true,
                'expired' => true,
                'error' => 'Email has expired',
            ], 410);
        }

        jsonResponse([
            'exists' => true,
            'expired' => false,
            'id' => (int) $emailData['id'],
            'email' => (string) $emailData['email'],
            'created_at' => (string) $emailData['created_at'],
        ]);
    }

    if ($method === 'DELETE') {
        $data = getJsonInput();
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        if ($email === '') {
            jsonResponse(['error' => 'email is required'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'invalid email format'], 400);
        }

        $stmt = $db->prepare("SELECT id FROM emails WHERE email = ?");
        $stmt->execute([$email]);
        $emailData = $stmt->fetch();

        if (!$emailData) {
            jsonResponse(['error' => 'Email not found'], 404);
        }

        $deleteStmt = $db->prepare("DELETE FROM emails WHERE id = ?");
        $deleteStmt->execute([(int) $emailData['id']]);

        jsonResponse([
            'success' => true,
            'deleted' => (int) $deleteStmt->rowCount(),
            'email' => $email,
        ]);
    }

    jsonResponse(['error' => 'Method not allowed'], 405);
} catch (Exception $e) {
    error_log("Email API error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}

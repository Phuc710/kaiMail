<?php
/**
 * User Email API
 * KaiMail - Temp Mail System
 * 
 * GET /api/emails?action=check&email=xxx - Kiểm tra email tồn tại
 * GET /api/emails?action=get&email=xxx - Lấy thông tin email
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'check';
$email = strtolower(trim($_GET['email'] ?? ''));

if (empty($email)) {
    jsonResponse(['error' => 'Email is required'], 400);
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Invalid email format'], 400);
}


try {
    $db = getDB();

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
            'error' => 'Email not found'
        ], 404);
    }

    // Check if expired
    if (!$emailData['is_expired'] && $emailData['expires_at']) {
        if (strtotime($emailData['expires_at']) < time()) {
            // Mark as expired
            $db->prepare("UPDATE emails SET is_expired = 1 WHERE id = ?")->execute([$emailData['id']]);
            $emailData['is_expired'] = 1;
        }
    }

    if ($emailData['is_expired']) {
        jsonResponse([
            'exists' => true,
            'expired' => true,
            'error' => 'Email has expired'
        ], 410);
    }

    jsonResponse([
        'exists' => true,
        'expired' => false,
        'email' => $emailData['email'],
        'created_at' => $emailData['created_at']
    ]);

} catch (Exception $e) {
    error_log("Email API error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}

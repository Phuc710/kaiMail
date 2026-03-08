<?php
/**
 * Admin Long Polling API
 * Monitors for new emails or messages system-wide.
 *
 * GET /api/admin/long-poll.php?last_check=2024-01-01 12:00:00
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../middleware/AdminSecurity.php';

header('Content-Type: application/json; charset=utf-8');
AdminSecurity::setNoCacheHeaders();
AdminSecurity::setCorsHeaders();
AdminSecurity::handlePreflight();
AdminSecurity::requireAdminAuth();

try {
    $lastCheck = $_GET['last_check'] ?? date('Y-m-d H:i:s');

    $maxTime = LONG_POLL_MAX_SECONDS;
    $sleepSeconds = LONG_POLL_SLEEP_SECONDS;
    $startTime = time();
    $db = getDB();

    $checkMessagesSql = "SELECT 1 FROM messages WHERE received_at > ? LIMIT 1";
    $checkEmailsSql = "SELECT 1 FROM emails WHERE created_at > ? LIMIT 1";

    $stmtMsg = $db->prepare($checkMessagesSql);
    $stmtEmail = $db->prepare($checkEmailsSql);

    while (time() - $startTime < $maxTime) {
        $stmtMsg->execute([$lastCheck]);
        $hasNewMsg = $stmtMsg->fetchColumn();

        $stmtEmail->execute([$lastCheck]);
        $hasNewEmail = $stmtEmail->fetchColumn();

        if ($hasNewMsg || $hasNewEmail) {
            echo json_encode([
                'has_updates' => true,
                'has_new' => true,
                'new_messages' => (bool) $hasNewMsg,
                'new_emails' => (bool) $hasNewEmail,
                'last_check' => date('Y-m-d H:i:s')
            ]);
            exit;
        }

        sleep($sleepSeconds);
    }

    echo json_encode([
        'has_updates' => false,
        'last_check' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    error_log('Admin long polling error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Long polling failed']);
}

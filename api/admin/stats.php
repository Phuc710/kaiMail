<?php
/**
 * Admin Stats API
 * KaiMail - Temp Mail System
 * 
 * GET /api/admin/stats - Dashboard statistics
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../middleware/AdminSecurity.php';

header('Content-Type: application/json; charset=utf-8');
AdminSecurity::setNoCacheHeaders();
AdminSecurity::setCorsHeaders();
AdminSecurity::handlePreflight();
AdminSecurity::requireAdminAuth();

if (getMethod() !== 'GET') {
    jsonResponse(['error' => 'Phương thức không được hỗ trợ'], 405);
}

try {
    $db = getDB();

    // Total emails
    $totalEmails = $db->query("SELECT COUNT(*) FROM emails")->fetchColumn();

    // Active emails
    $activeEmails = $totalEmails;

    // Expired emails
    $expiredEmails = 0;

    // Total messages
    $totalMessages = $db->query("SELECT COUNT(*) FROM messages")->fetchColumn();

    // Unread messages
    $unreadMessages = $db->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();

    // Legacy field kept for backward compatibility with dashboard consumers.
    $byExpiry = [];

    // Recent emails (last 7 days)
    $recentEmails = $db->query("
        SELECT COUNT(*) FROM emails 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetchColumn();

    // Recent messages (last 7 days)
    $recentMessages = $db->query("
        SELECT COUNT(*) FROM messages 
        WHERE received_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetchColumn();

    jsonResponse([
        'total_emails' => (int) $totalEmails,
        'active_emails' => (int) $activeEmails,
        'expired_emails' => (int) $expiredEmails,
        'total_messages' => (int) $totalMessages,
        'unread_messages' => (int) $unreadMessages,
        'by_expiry' => $byExpiry,
        'recent_emails' => (int) $recentEmails,
        'recent_messages' => (int) $recentMessages,
        'server_time' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log("Stats error: " . $e->getMessage());
    jsonResponse(['error' => 'Lỗi hệ thống'], 500);
}

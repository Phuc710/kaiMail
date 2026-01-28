<?php
/**
 * User Messages API
 * KaiMail - Temp Mail System
 * 
 * GET /api/messages?email=xxx - Lấy messages của email
 * GET /api/messages?id=xxx - Lấy chi tiết message
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDB();

    // Get single message by ID
    if (isset($_GET['id'])) {
        $messageId = (int) $_GET['id'];

        $stmt = $db->prepare("
            SELECT m.*, e.email 
            FROM messages m 
            JOIN emails e ON m.email_id = e.id 
            WHERE m.id = ? AND e.is_expired = 0
        ");
        $stmt->execute([$messageId]);
        $message = $stmt->fetch();

        if ($message) {
            // Decode Subject if MIME encoded
            if (!empty($message['subject']) && strpos($message['subject'], '=?') === 0) {
                $message['subject'] = iconv_mime_decode($message['subject'], 0, "UTF-8");
            }

            // Decode Body Content (Quoted-Printable)
            if ($message['body_html']) {
                if (preg_match('/=[0-9A-F]{2}/', $message['body_html'])) {
                    $message['body_html'] = quoted_printable_decode($message['body_html']);
                }
            }

            if ($message['body_text']) {
                if (preg_match('/=[0-9A-F]{2}/', $message['body_text'])) {
                    $message['body_text'] = quoted_printable_decode($message['body_text']);
                }
            }
        }

        if (!$message) {
            jsonResponse(['error' => 'Message not found'], 404);
        }

        // Mark as read
        $db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([$messageId]);

        jsonResponse([
            'id' => $message['id'],
            'from_email' => $message['from_email'],
            'from_name' => $message['from_name'],
            'subject' => $message['subject'],
            'body_text' => $message['body_text'],
            'body_html' => $message['body_html'],
            'is_read' => true,
            'received_at' => $message['received_at']
        ]);
    }

    // Get messages by email
    $email = strtolower(trim($_GET['email'] ?? ''));

    if (empty($email)) {
        jsonResponse(['error' => 'Email is required'], 400);
    }

    // Get email record
    $stmt = $db->prepare("SELECT id, is_expired FROM emails WHERE email = ?");
    $stmt->execute([$email]);
    $emailData = $stmt->fetch();

    if (!$emailData) {
        jsonResponse(['error' => 'Email not found'], 404);
    }

    if ($emailData['is_expired']) {
        jsonResponse(['error' => 'Email has expired'], 410);
    }

    // Get messages
    $stmt = $db->prepare("
        SELECT id, from_email, from_name, subject, is_read, received_at,
               LEFT(body_text, 100) as preview
        FROM messages 
        WHERE email_id = ?
        ORDER BY received_at DESC
        LIMIT 100
    ");
    $stmt->execute([$emailData['id']]);
    $messages = $stmt->fetchAll();

    // Process preview text and subject
    foreach ($messages as &$msg) {
        if (!empty($msg['subject']) && strpos($msg['subject'], '=?') === 0) {
            $msg['subject'] = iconv_mime_decode($msg['subject'], 0, "UTF-8");
        }
        // Basic quoted-printable decode for preview if needed
        if (!empty($msg['preview']) && strpos($msg['preview'], '=') !== false) {
            $msg['preview'] = quoted_printable_decode($msg['preview']);
        }
    }
    unset($msg);

    // Count unread
    $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE email_id = ? AND is_read = 0");
    $stmt->execute([$emailData['id']]);
    $unreadCount = $stmt->fetchColumn();

    jsonResponse([
        'email' => $email,
        'total' => count($messages),
        'unread' => (int) $unreadCount,
        'messages' => $messages
    ]);

} catch (Exception $e) {
    error_log("Messages API error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}

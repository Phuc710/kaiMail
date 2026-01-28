<?php
/**
 * Admin Messages API
 * KaiMail - Temp Mail System
 * 
 * GET    /api/admin/messages - List messages
 * GET    /api/admin/messages?id=x - Get single message
 * DELETE /api/admin/messages - Delete message(s)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/Auth.php';

header('Content-Type: application/json; charset=utf-8');

Auth::requireLogin();

$method = getMethod();
$db = getDB();

try {
    // =====================
    // GET - List/Get messages
    // =====================
    if ($method === 'GET') {
        // Get single message
        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            $stmt = $db->prepare("
                SELECT m.*, UNIX_TIMESTAMP(m.received_at) as ts, e.email 
                FROM messages m 
                JOIN emails e ON m.email_id = e.id 
                WHERE m.id = ?
            ");
            $stmt->execute([$id]);
            $message = $stmt->fetch();

            if ($message) {
                // Decode Subject if MIME encoded
                if (strpos($message['subject'], '=?') === 0) {
                    $message['subject'] = iconv_mime_decode($message['subject'], 0, "UTF-8");
                }

                // Decode Body Content (Quoted-Printable)
                if ($message['body_html']) {
                    // Check if likely quoted-printable (contains =3D or =20 or similar patterns)
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
            $db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([$id]);

            // Return formatted response
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

        // List messages for email
        $emailId = (int) ($_GET['email_id'] ?? 0);

        if (!$emailId) {
            jsonResponse(['error' => 'email_id required'], 400);
        }

        $stmt = $db->prepare("
            SELECT id, from_email, from_name, subject, is_read, received_at,
                   UNIX_TIMESTAMP(received_at) as ts,
                   LEFT(body_text, 100) as preview
            FROM messages 
            WHERE email_id = ?
            ORDER BY received_at DESC
        ");
        $stmt->execute([$emailId]);
        $messages = $stmt->fetchAll();

        // Process preview text and subject
        foreach ($messages as &$msg) {
            if (strpos($msg['subject'], '=?') === 0) {
                $msg['subject'] = iconv_mime_decode($msg['subject'], 0, "UTF-8");
            }
            // Basic quoted-printable decode for preview if needed
            if (strpos($msg['preview'], '=') !== false) {
                $msg['preview'] = quoted_printable_decode($msg['preview']);
            }
        }

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

        // Debug logging

        $ids = $data['ids'] ?? [];
        $emailId = (int) ($data['email_id'] ?? 0);
        $deleteAll = $data['delete_all'] ?? false;

        if ($deleteAll && $emailId) {
            $stmt = $db->prepare("DELETE FROM messages WHERE email_id = ?");
            $stmt->execute([$emailId]);
            $deleted = $stmt->rowCount();
        } elseif (!empty($ids)) {
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "DELETE FROM messages WHERE id IN ($placeholders)";


            $stmt = $db->prepare($sql);
            $stmt->execute($ids);
            $deleted = $stmt->rowCount();
        } else {
            jsonResponse(['error' => 'No messages specified', 'received' => $data], 400);
        }

        jsonResponse([
            'success' => true,
            'deleted' => $deleted
        ]);
    } else {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

} catch (Exception $e) {
    jsonResponse(['error' => 'Internal server error'], 500);
}

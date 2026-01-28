<?php
/**
 * Long Polling API for Messages
 * KaiMail - Temp Mail System
 * 
 * Returns new messages for an email after a specific timestamp
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

$method = getMethod();
$db = getDB();

try {
    if ($method === 'GET') {
        $emailId = (int) ($_GET['email_id'] ?? 0);
        $lastCheck = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-1 hour'));
        $timeout = 30; // 30 seconds timeout
        $startTime = time();

        if (!$emailId) {
            jsonResponse(['error' => 'email_id required'], 400);
        }

        // Long polling loop
        while ((time() - $startTime) < $timeout) {
            // Check for new messages
            $stmt = $db->prepare("
                SELECT id, from_email, from_name, subject, is_read, received_at,
                       UNIX_TIMESTAMP(received_at) as ts,
                       LEFT(body_text, 100) as preview
                FROM messages 
                WHERE email_id = ? AND received_at > ?
                ORDER BY received_at DESC
            ");
            $stmt->execute([$emailId, $lastCheck]);
            $newMessages = $stmt->fetchAll();

            if (count($newMessages) > 0) {
                // Process messages
                foreach ($newMessages as &$msg) {
                    if (strpos($msg['subject'], '=?') === 0) {
                        $msg['subject'] = iconv_mime_decode($msg['subject'], 0, "UTF-8");
                    }
                    if (strpos($msg['preview'], '=') !== false) {
                        $msg['preview'] = quoted_printable_decode($msg['preview']);
                    }
                }

                jsonResponse([
                    'has_new' => true,
                    'count' => count($newMessages),
                    'messages' => $newMessages,
                    'last_check' => date('Y-m-d H:i:s')
                ]);
            }

            // Sleep for a bit before checking again
            usleep(500000); // 0.5 seconds
        }

        // Timeout - no new messages
        jsonResponse([
            'has_new' => false,
            'count' => 0,
            'messages' => [],
            'last_check' => date('Y-m-d H:i:s')
        ]);
    } else {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => 'Internal server error'], 500);
}

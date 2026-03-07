<?php
/**
 * Long Polling API
 * Allows real-time checking of new messages
 * 
 * GET /api/poll.php?email_id=123&last_check=2024-01-01 12:00:00
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/DatabaseOptimizer.php';
require_once __DIR__ . '/middleware/ApiSecurity.php';

// Disable caching strongly
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
ApiSecurity::setCorsHeaders();
ApiSecurity::handlePreflight();
ApiSecurity::requireApiAuth();

try {
    $emailId = (int) ($_GET['email_id'] ?? 0);
    $lastCheck = $_GET['last_check'] ?? date('Y-m-d H:i:s');

    // Safety limit from environment configuration
    $maxTime = LONG_POLL_MAX_SECONDS;
    $sleepSeconds = LONG_POLL_SLEEP_SECONDS;
    $startTime = time();
    $db = getDB();
    DatabaseOptimizer::ensureCoreIndexes($db);

    if (!$emailId) {
        jsonResponse(['error' => 'email_id required'], 400);
    }

    // Query once per loop: directly fetch newest messages after last_check.
    $fetchSql = "
        SELECT id, from_email, from_name, subject, is_read, received_at,
               LEFT(body_text, 100) as preview
        FROM messages
        WHERE email_id = ? AND received_at > ?
        ORDER BY received_at DESC
        LIMIT 20
    ";
    $stmt = $db->prepare($fetchSql);

    while (time() - $startTime < $maxTime) {
        $stmt->closeCursor();

        $stmt->execute([$emailId, $lastCheck]);
        $messages = $stmt->fetchAll();
        $count = is_array($messages) ? count($messages) : 0;

        if ($count > 0) {
            jsonResponse([
                'has_new' => true,
                'count' => $count,
                'messages' => $messages,
                'last_check' => date('Y-m-d H:i:s')
            ]);
        }

        // Wait 1 second before next check
        // Using sleep helps reduce CPU usage
        sleep($sleepSeconds);

        // Close connection during sleep if possible to avoid connection limit?
        // No, keep persistent is better for short poll.
    }

    // Timeout reached, return empty (client will reconnect)
    jsonResponse([
        'has_new' => false,
        'count' => 0,
        'last_check' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    // Log error but don't expose details
    error_log("Polling error: " . $e->getMessage());
    jsonResponse(['error' => 'Polling failed'], 500);
}

<?php
/**
 * Fast Email Checker API - ULTIMATE SPEED VERSION
 * 
 * Features:
 * 1. O(1) Domain ID lookup instead of slow '%@domain' LIKE matches.
 * 2. FULLTEXT MySQL Index for sub-millisecond keyword scanning on millions of rows.
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
    jsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    $db = getDB();

    $keyword = trim((string) ($_GET['keyword'] ?? 'deactivating'));
    $domain = trim((string) ($_GET['domain'] ?? ''));
    $limit = min(1000, max(1, (int) ($_GET['limit'] ?? 100)));
    $days = max(1, (int) ($_GET['days'] ?? 7));

    if ($keyword === '') {
        jsonResponse(['error' => 'Keyword is required'], 400);
    }

    // Thuật toán Boolean FTS (yêu cầu mysql FULLTEXT):
    // Thay dấu cách bằng + (bắt buộc có tất cả từ khóa)
    $ftsTerm = '';
    $words = array_filter(explode(' ', $keyword));
    if (!empty($words)) {
        $ftsTerm = '+' . implode(' +', $words);
    } else {
        $ftsTerm = '+' . $keyword;
    }

    $where = ["m.received_at >= DATE_SUB(NOW(), INTERVAL ? DAY)"];
    $params = [$days];

    // TỐI ƯU 1: DÙNG DOMAIN_ID THAY VÌ LIKE '%@domain' ĐỂ TRÁNH QUÉT TOÀN BỘ BẢNG `emails`
    if ($domain !== '') {
        $stmtDomain = $db->prepare("SELECT id FROM domains WHERE domain = ? LIMIT 1");
        $stmtDomain->execute([$domain]);
        $domainRow = $stmtDomain->fetch();
        if (!$domainRow) {
            jsonResponse([
                'success' => true,
                'count' => 0,
                'keyword' => $keyword,
                'results' => [],
                'execution_time' => '0s',
                'note' => 'Domain not found'
            ]);
        }
        $where[] = "e.domain_id = ?";
        $params[] = (int) $domainRow['id'];
    }

    // TỐI ƯU 2: Sử dụng MATCH() AGAINST() với index `idx_content_search` bạn vừa tạo
    $where[] = "MATCH(m.subject, m.body_text) AGAINST (? IN BOOLEAN MODE)";
    $params[] = $ftsTerm;

    // Truy vấn để tìm ra email & tin nhắn gần nhất khớp keyword
    // Subquery m.id = MAX(m2.id) cũng áp dụng FTS để cực kỳ chính xác và nhanh
    $sql = "
        SELECT 
            e.id as email_id,
            e.email,
            m.subject,
            m.received_at,
            m.id as message_id,
            m.body_text,
            m.body_html
        FROM emails e
        JOIN messages m ON m.email_id = e.id
        WHERE " . implode(" AND ", $where) . "
        AND m.id = (
            SELECT MAX(m2.id) 
            FROM messages m2 
            WHERE m2.email_id = e.id 
            AND m2.received_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            AND MATCH(m2.subject, m2.body_text) AGAINST (? IN BOOLEAN MODE)
        )
        ORDER BY m.received_at DESC
        LIMIT ?
    ";

    // Gắn tham số subquery
    $params[] = $days;
    $params[] = $ftsTerm;

    // Gắn Limit
    $params[] = $limit;

    $startTime = microtime(true);

    // Fallback: Nếu user chưa chạy lệnh FULLTEXT MySQL hoặc bị lỗi bảng
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
    } catch (PDOException $pdoEx) {
        // Fallback về `LIKE` nếu MySQL báo lỗi vì thiếu FULLTEXT index
        if (strpos($pdoEx->getMessage(), 'FULLTEXT') !== false || strpos($pdoEx->getMessage(), 'MATCH') !== false) {
            error_log("FTS Error, falling back to LIKE: " . $pdoEx->getMessage());

            // Xây dựng lại params cho LIKE O(N)
            $paramsLike = [$days];
            $whereLike = ["m.received_at >= DATE_SUB(NOW(), INTERVAL ? DAY)"];
            if ($domain !== '') {
                $whereLike[] = "e.domain_id = ?";
                $paramsLike[] = (int) $domainRow['id'];
            }

            $searchTerm = "%{$keyword}%";
            $whereLike[] = "(m.subject LIKE ? OR m.body_text LIKE ?)";
            $paramsLike[] = $searchTerm;
            $paramsLike[] = $searchTerm;

            $sqlFallback = "
                SELECT 
                    e.id as email_id,
                    e.email,
                    m.subject,
                    m.received_at,
                    m.id as message_id,
                    m.body_text,
                    m.body_html
                FROM emails e
                JOIN messages m ON m.email_id = e.id
                WHERE " . implode(" AND ", $whereLike) . "
                AND m.id = (
                    SELECT MAX(m2.id) 
                    FROM messages m2 
                    WHERE m2.email_id = e.id 
                    AND m2.received_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    AND (m2.subject LIKE ? OR m2.body_text LIKE ?)
                )
                ORDER BY m.received_at DESC
                LIMIT ?
            ";

            $paramsLike[] = $days;
            $paramsLike[] = $searchTerm;
            $paramsLike[] = $searchTerm;
            $paramsLike[] = $limit;

            $stmtFallback = $db->prepare($sqlFallback);
            $stmtFallback->execute($paramsLike);
            $results = $stmtFallback->fetchAll();
        } else {
            throw $pdoEx; // Quăng lỗi lên try/catch bên ngoài nếu là lỗi khác
        }
    }

    $endTime = microtime(true);

    jsonResponse([
        'success' => true,
        'count' => count($results),
        'keyword' => $keyword,
        'results' => $results,
        'execution_time' => round($endTime - $startTime, 4) . 's',
        'server_time' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log("Fast Checker API error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
}

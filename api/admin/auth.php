<?php
/**
 * Admin Authentication API (không dùng cookie/session).
 *
 * POST   /api/admin/auth.php - Kiểm tra ADMIN_ACCESS_KEY
 * GET    /api/admin/auth.php - Kiểm tra header xác thực hiện tại
 * DELETE /api/admin/auth.php - Đăng xuất logic phía client
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../middleware/AdminSecurity.php';

header('Content-Type: application/json; charset=utf-8');
AdminSecurity::setCorsHeaders();
AdminSecurity::handlePreflight();

$method = getMethod();

if ($method === 'POST') {
    $data = getJsonInput();
    $password = trim((string) ($data['password'] ?? ''));

    // Hỗ trợ dán nguyên dòng từ .env: ADMIN_ACCESS_KEY=xxxx
    if (str_starts_with($password, 'ADMIN_ACCESS_KEY=')) {
        $password = substr($password, strlen('ADMIN_ACCESS_KEY='));
    }

    if ($password === '') {
        jsonResponse(['error' => 'Khóa truy cập là bắt buộc'], 400);
    }

    if (!hash_equals((string) ADMIN_ACCESS_KEY, $password)) {
        jsonResponse(['error' => 'Khóa truy cập không đúng'], 401);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Xác thực thành công',
        'auth_type' => 'admin_access_key',
        'server_time' => date('Y-m-d H:i:s'),
    ]);
}

if ($method === 'GET') {
    AdminSecurity::requireAdminAuth();
    jsonResponse([
        'authenticated' => true,
        'auth_type' => 'admin_access_key',
        'server_time' => date('Y-m-d H:i:s'),
    ]);
}

if ($method === 'DELETE') {
    jsonResponse([
        'success' => true,
        'message' => 'Đã đăng xuất phía client',
    ]);
}

jsonResponse(['error' => 'Method không được hỗ trợ'], 405);

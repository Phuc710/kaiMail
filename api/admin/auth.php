<?php
declare(strict_types=1);

/**
 * Admin Authentication API (stateless).
 *
 * POST   /api/admin/auth.php - verify ADMIN_ACCESS_KEY
 * GET    /api/admin/auth.php - verify current auth header
 * DELETE /api/admin/auth.php - client-side logical logout
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../middleware/AdminSecurity.php';

header('Content-Type: application/json; charset=utf-8');
AdminSecurity::setCorsHeaders();
AdminSecurity::handlePreflight();

$method = getMethod();

if ($method === 'POST') {
    AdminSecurity::enforceLoginRateLimit();
    AdminSecurity::enforceNetworkPolicyOnly();

    $data = getJsonInput();
    $password = trim((string) ($data['password'] ?? ''));

    // Support pasting full .env line format: ADMIN_ACCESS_KEY=xxxx
    if (str_starts_with($password, 'ADMIN_ACCESS_KEY=')) {
        $password = substr($password, strlen('ADMIN_ACCESS_KEY='));
    }

    if ($password === '') {
        jsonResponse(['error' => 'Access key is required'], 400);
    }

    if (!hash_equals((string) ADMIN_ACCESS_KEY, $password)) {
        // Slow down brute-force attempts slightly.
        usleep(250000);
        jsonResponse(['error' => 'Invalid access key'], 401);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Authenticated',
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
        'message' => 'Logged out on client side',
    ]);
}

jsonResponse(['error' => 'Method not allowed'], 405);

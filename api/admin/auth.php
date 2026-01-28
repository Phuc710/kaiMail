<?php
/**
 * Admin Authentication API
 * KaiMail - Temp Mail System
 * 
 * POST /api/admin/auth - Login
 * DELETE /api/admin/auth - Logout
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/Auth.php';

header('Content-Type: application/json; charset=utf-8');

$method = getMethod();

if ($method === 'POST') {
    // Login with access key
    $data = getJsonInput();
    $password = $data['password'] ?? '';

    if (empty($password)) {
        jsonResponse(['error' => 'Access key is required'], 400);
    }

    // Hardcoded access key
    if ($password === 'kaishop@2026') {
        Auth::startSession();
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_username'] = 'admin';
        $_SESSION['logged_in'] = true;

        jsonResponse([
            'success' => true,
            'message' => 'Login successful',
            'admin' => ['id' => 1, 'username' => 'admin']
        ]);
    } else {
        jsonResponse(['error' => 'Invalid access key'], 401);
    }
} elseif ($method === 'DELETE') {
    // Logout
    Auth::logout();
    jsonResponse(['success' => true, 'message' => 'Logged out']);
} elseif ($method === 'GET') {
    // Check session
    if (Auth::isLoggedIn()) {
        jsonResponse([
            'logged_in' => true,
            'admin' => Auth::getAdmin()
        ]);
    } else {
        jsonResponse(['logged_in' => false], 401);
    }
} else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

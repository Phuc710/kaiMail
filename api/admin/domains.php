<?php
/**
 * Admin Domains API
 * KaiMail - Temp Mail System
 * 
 * POST   /api/admin/domains - Add new domain
 * GET    /api/admin/domains - List all domains
 * PUT    /api/admin/domains - Update domain status
 * DELETE /api/admin/domains - Delete domain
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/Auth.php';

header('Content-Type: application/json; charset=utf-8');

// Require admin login
Auth::requireLogin();

$method = getMethod();
$db = getDB();

try {
    // =====================
    // POST - Add domain
    // =====================
    if ($method === 'POST') {
        $data = getJsonInput();

        $domain = strtolower(trim($data['domain'] ?? ''));
        $isActive = isset($data['is_active']) ? (int) $data['is_active'] : 1;

        if (empty($domain)) {
            jsonResponse(['error' => 'Domain name is required'], 400);
        }

        // Validate domain format
        if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain)) {
            jsonResponse(['error' => 'Invalid domain format'], 400);
        }

        // Check if domain already exists
        $stmt = $db->prepare("SELECT id FROM domains WHERE domain = ?");
        $stmt->execute([$domain]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'Domain already exists'], 400);
        }

        // Insert domain
        $stmt = $db->prepare("
            INSERT INTO domains (domain, is_active)
            VALUES (?, ?)
        ");
        $stmt->execute([$domain, $isActive]);

        jsonResponse([
            'success' => true,
            'id' => $db->lastInsertId(),
            'domain' => $domain,
            'is_active' => $isActive
        ], 201);
    }

    // =====================
    // GET - List domains
    // =====================
    elseif ($method === 'GET') {
        $stmt = $db->query("
            SELECT id, domain, is_active, created_at
            FROM domains
            ORDER BY is_active DESC, domain ASC
        ");
        $domains = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'domains' => $domains
        ]);
    }

    // =====================
    // PUT - Update domain
    // =====================
    elseif ($method === 'PUT') {
        $data = getJsonInput();

        $id = (int) ($data['id'] ?? 0);
        $isActive = isset($data['is_active']) ? (int) $data['is_active'] : null;

        if (!$id) {
            jsonResponse(['error' => 'Domain ID is required'], 400);
        }

        if ($isActive !== null) {
            $stmt = $db->prepare("UPDATE domains SET is_active = ? WHERE id = ?");
            $stmt->execute([$isActive, $id]);
        }

        jsonResponse(['success' => true]);
    }

    // =====================
    // DELETE - Delete domain
    // =====================
    elseif ($method === 'DELETE') {
        $data = getJsonInput();
        $id = (int) ($data['id'] ?? 0);

        if (!$id) {
            jsonResponse(['error' => 'Domain ID is required'], 400);
        }

        // Check if domain is in use
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM emails 
            WHERE email LIKE CONCAT('%@', (SELECT domain FROM domains WHERE id = ?))
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        if ($result['count'] > 0) {
            jsonResponse([
                'error' => 'Cannot delete domain that has existing emails',
                'email_count' => $result['count']
            ], 400);
        }

        $stmt = $db->prepare("DELETE FROM domains WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['success' => true]);
    } else {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

} catch (Exception $e) {
    error_log("Admin domains error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}

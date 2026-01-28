<?php
/**
 * Authentication Helper
 * KaiMail - Temp Mail System  
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

class Auth
{
    /**
     * Start session
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
    }

    /**
     * Login admin
     */
    public static function login(string $username, string $password): bool
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            self::startSession();
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['logged_in'] = true;
            return true;
        }

        return false;
    }

    /**
     * Check if admin is logged in
     */
    public static function isLoggedIn(): bool
    {
        self::startSession();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Logout admin
     */
    public static function logout(): void
    {
        self::startSession();
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Require admin login
     */
    public static function requireLogin(): void
    {
        // Check for URL key bypass
        $urlKey = $_GET['key'] ?? '';
        if ($urlKey === 'kaishop@2026') {
            self::startSession();
            $_SESSION['admin_id'] = 1;
            $_SESSION['admin_username'] = 'admin';
            $_SESSION['logged_in'] = true;
            return;
        }

        if (!self::isLoggedIn()) {
            if (isAjax()) {
                jsonResponse(['error' => 'Unauthorized'], 401);
            } else {
                // Redirect to login page (clean URL)
                header('Location: ' . BASE_URL . '/adminkaishop/login');
                exit;
            }
        }
    }

    /**
     * Get current admin
     */
    public static function getAdmin(): ?array
    {
        self::startSession();
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['admin_id'],
                'username' => $_SESSION['admin_username']
            ];
        }
        return null;
    }

    /**
     * Hash password
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

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
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_httponly', SESSION_COOKIE_HTTP_ONLY ? '1' : '0');
            ini_set('session.cookie_secure', SESSION_COOKIE_SECURE ? '1' : '0');

            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => SESSION_COOKIE_PATH,
                'domain' => SESSION_COOKIE_DOMAIN,
                'secure' => SESSION_COOKIE_SECURE,
                'httponly' => SESSION_COOKIE_HTTP_ONLY,
                'samesite' => SESSION_COOKIE_SAMESITE,
            ]);

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
            session_regenerate_id(true);
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
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? SESSION_COOKIE_SAMESITE,
            ]);
        }

        session_destroy();
    }

    /**
     * Require admin login
     */
    public static function requireLogin(): void
    {
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

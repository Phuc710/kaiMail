<?php
declare(strict_types=1);

require_once __DIR__ . '/RateLimiter.php';

/**
 * Security middleware for admin UI API only.
 *
 * Auth method:
 * - X-ADMIN-ACCESS-KEY
 */
final class AdminSecurity
{
    public static function setNoCacheHeaders(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }

    public static function setCorsHeaders(): void
    {
        $baseUrlParts = parse_url(BASE_URL);
        $allowedOrigin = '';
        if (!empty($baseUrlParts['scheme']) && !empty($baseUrlParts['host'])) {
            $allowedOrigin = $baseUrlParts['scheme'] . '://' . $baseUrlParts['host'];
            if (!empty($baseUrlParts['port'])) {
                $allowedOrigin .= ':' . $baseUrlParts['port'];
            }
        }

        $requestOrigin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
        if ($allowedOrigin !== '' && $requestOrigin !== '' && rtrim($requestOrigin, '/') === rtrim($allowedOrigin, '/')) {
            header('Access-Control-Allow-Origin: ' . $requestOrigin);
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-ADMIN-ACCESS-KEY');
        header('Access-Control-Max-Age: 600');
    }

    public static function handlePreflight(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'OPTIONS') {
            return;
        }

        http_response_code(204);
        exit;
    }

    public static function requireAdminAuth(): void
    {
        self::enforceNetworkPolicy();
        self::enforceRateLimit();

        if (self::verifyAdminAccessKeyHeader()) {
            return;
        }

        self::deny(401, 'Khong hop le hoac thieu X-ADMIN-ACCESS-KEY');
    }

    public static function enforceNetworkPolicyOnly(): void
    {
        self::enforceNetworkPolicy();
    }

    public static function verifyAdminAccessKeyHeader(): bool
    {
        $adminAccessKey = self::getHeader('X-ADMIN-ACCESS-KEY');
        if ($adminAccessKey === '') {
            return false;
        }

        return hash_equals((string) ADMIN_ACCESS_KEY, $adminAccessKey);
    }

    public static function enforceLoginRateLimit(): void
    {
        $identifier = self::getClientIp();
        $result = RateLimiter::enforce('admin_login', ADMIN_LOGIN_RATE_LIMIT_PER_MIN, 60, $identifier !== '' ? $identifier : 'unknown');
        header('X-RateLimit-Limit: ' . $result['limit']);
        header('X-RateLimit-Remaining: ' . $result['remaining']);
        header('X-RateLimit-Reset: ' . $result['reset_at']);

        if (!$result['allowed']) {
            self::deny(429, 'Too many login attempts', ['retry_after' => $result['retry_after']]);
        }
    }

    private static function getHeader(string $name): string
    {
        $normalized = strtoupper(str_replace('-', '_', $name));
        $candidates = [
            'HTTP_' . $normalized,
            $normalized,
        ];

        foreach ($candidates as $key) {
            if (isset($_SERVER[$key])) {
                return trim((string) $_SERVER[$key]);
            }
        }

        if (function_exists('getallheaders')) {
            foreach ((array) getallheaders() as $headerName => $value) {
                if (strtoupper(str_replace('-', '_', (string) $headerName)) === $normalized) {
                    return trim((string) $value);
                }
            }
        }

        return '';
    }

    private static function enforceRateLimit(): void
    {
        $identifier = self::getClientIp();
        $result = RateLimiter::enforce('admin_api', ADMIN_RATE_LIMIT_PER_MIN, 60, $identifier !== '' ? $identifier : 'unknown');
        header('X-RateLimit-Limit: ' . $result['limit']);
        header('X-RateLimit-Remaining: ' . $result['remaining']);
        header('X-RateLimit-Reset: ' . $result['reset_at']);

        if (!$result['allowed']) {
            self::deny(429, 'Too many requests', ['retry_after' => $result['retry_after']]);
        }
    }

    private static function enforceNetworkPolicy(): void
    {
        if (ADMIN_REQUIRE_HTTPS && !self::isHttpsRequest() && !self::isLocalDevelopmentRequest()) {
            self::deny(403, 'Bat buoc dung HTTPS');
        }
    }

    private static function isHttpsRequest(): bool
    {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off') {
            return true;
        }

        if (API_TRUST_PROXY_HEADERS) {
            $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
            if ($forwardedProto === 'https') {
                return true;
            }
        }

        return ((string) ($_SERVER['SERVER_PORT'] ?? '')) === '443';
    }

    private static function isLocalDevelopmentRequest(): bool
    {
        $appEnv = strtolower((string) (defined('ENVIRONMENT') ? ENVIRONMENT : ''));
        if ($appEnv === 'local' || $appEnv === 'development') {
            return true;
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        $host = preg_replace('/:\d+$/', '', $host);
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        return in_array($remoteAddr, ['127.0.0.1', '::1'], true);
    }

    private static function getClientIp(): string
    {
        if (API_TRUST_PROXY_HEADERS) {
            $cfIp = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
            if ($cfIp !== '') {
                return $cfIp;
            }

            $xff = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
            if ($xff !== '') {
                $parts = array_map('trim', explode(',', $xff));
                if (isset($parts[0]) && $parts[0] !== '') {
                    return $parts[0];
                }
            }
        }

        return trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    }

    private static function deny(int $statusCode, string $message, array $extra = []): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge([
            'error' => 'Unauthorized',
            'message' => $message,
        ], $extra), JSON_UNESCAPED_UNICODE);
        exit;
    }
}

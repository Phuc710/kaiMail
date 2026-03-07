<?php
declare(strict_types=1);

/**
 * Security middleware for admin UI API only.
 *
 * Auth method:
 * - X-ADMIN-ACCESS-KEY
 */
final class AdminSecurity
{
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
        if (self::verifyAdminAccessKeyHeader()) {
            return;
        }

        self::deny(401, 'Khong hop le hoac thieu X-ADMIN-ACCESS-KEY');
    }

    public static function verifyAdminAccessKeyHeader(): bool
    {
        $adminAccessKey = self::getHeader('X-ADMIN-ACCESS-KEY');
        if ($adminAccessKey === '') {
            return false;
        }

        return hash_equals((string) ADMIN_ACCESS_KEY, $adminAccessKey);
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

    private static function deny(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => 'Unauthorized',
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

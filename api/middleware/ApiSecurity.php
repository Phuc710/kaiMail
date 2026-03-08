<?php
declare(strict_types=1);

require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/ReplayGuard.php';

/**
 * Security middleware for external/public API.
 *
 * Auth method:
 * - X-API-KEY + X-API-TIMESTAMP + X-API-NONCE + X-API-SIGNATURE
 */
final class ApiSecurity
{
    private static ?string $rawBody = null;

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
        header('Access-Control-Allow-Headers: Content-Type, X-API-KEY, X-API-TIMESTAMP, X-API-NONCE, X-API-SIGNATURE, X-WEB-UI-TOKEN');
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

    public static function requireApiAuth(): void
    {
        self::enforceNetworkPolicy();
        self::enforceRateLimit();

        if (self::verifyWebUiSessionToken()) {
            return;
        }

        $apiKey = self::getHeader('X-API-KEY');
        $timestampHeader = self::getHeader('X-API-TIMESTAMP');
        $nonce = self::getHeader('X-API-NONCE');
        $signature = self::getHeader('X-API-SIGNATURE');

        if ($apiKey === '' || $timestampHeader === '' || $signature === '') {
            self::deny(401, 'Thieu header xac thuc API');
        }

        if (!hash_equals((string) API_ACCESS_KEY, $apiKey)) {
            self::deny(401, 'API key khong hop le');
        }

        $timestamp = (int) $timestampHeader;
        if ($timestamp <= 0) {
            self::deny(401, 'Timestamp khong hop le');
        }

        if (abs(time() - $timestamp) > API_REQUEST_TTL) {
            self::deny(401, 'Yeu cau da het thoi gian hop le');
        }

        if (API_REQUIRE_NONCE && $nonce === '') {
            self::deny(401, 'Thieu X-API-NONCE');
        }

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $payloadHash = hash('sha256', self::getRawBody());
        $payload = $method . "\n" . $path . "\n" . $timestamp . "\n" . $nonce . "\n" . $payloadHash;
        $expectedSignature = hash_hmac('sha256', $payload, (string) API_SECRET_KEY);

        if (!hash_equals($expectedSignature, $signature)) {
            self::deny(401, 'Chu ky API khong hop le');
        }

        if (API_REQUIRE_NONCE && !ReplayGuard::verifyNonce('api', $nonce, API_NONCE_TTL)) {
            self::deny(401, 'Nonce da duoc su dung hoac khong hop le');
        }
    }

    private static function enforceNetworkPolicy(): void
    {
        if (API_REQUIRE_HTTPS && !self::isHttpsRequest() && !self::isLocalDevelopmentRequest()) {
            self::deny(403, 'Bat buoc dung HTTPS');
        }

        if (API_STRICT_MODE && !self::isLocalDevelopmentRequest() && trim((string) API_ALLOWED_IPS) === '') {
            self::deny(403, 'API_STRICT_MODE yeu cau khai bao API_ALLOWED_IPS');
        }

        if (!self::isIpAllowed(self::getClientIp())) {
            self::deny(403, 'IP khong duoc phep');
        }
    }

    private static function enforceRateLimit(): void
    {
        $clientIp = self::getClientIp();
        $apiKey = self::getHeader('X-API-KEY');
        $identifier = ($clientIp !== '' ? $clientIp : 'unknown') . '|' . substr($apiKey, 0, 16);

        $limit = RateLimiter::enforce('api', API_RATE_LIMIT_PER_MIN, 60, $identifier);
        header('X-RateLimit-Limit: ' . $limit['limit']);
        header('X-RateLimit-Remaining: ' . $limit['remaining']);
        header('X-RateLimit-Reset: ' . $limit['reset_at']);

        if (!$limit['allowed']) {
            self::deny(429, 'Too many requests', ['retry_after' => $limit['retry_after']]);
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

    private static function verifyWebUiSessionToken(): bool
    {
        if (!API_ALLOW_SESSION_FALLBACK) {
            return false;
        }

        if (!self::isSameOriginRequest()) {
            return false;
        }

        $token = self::getHeader('X-WEB-UI-TOKEN');
        if ($token === '') {
            return false;
        }

        self::startSessionForFallback();

        $sessionToken = trim((string) ($_SESSION['kaimail_web_ui_token'] ?? ''));
        if ($sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    private static function startSessionForFallback(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (defined('SESSION_NAME') && SESSION_NAME !== '') {
            session_name((string) SESSION_NAME);
        }

        $secureCookie = (defined('SESSION_COOKIE_SECURE') ? (bool) SESSION_COOKIE_SECURE : false) && self::isHttpsRequest();
        $cookieParams = [
            'lifetime' => defined('SESSION_LIFETIME') ? (int) SESSION_LIFETIME : 86400,
            'path' => defined('SESSION_COOKIE_PATH') ? (string) SESSION_COOKIE_PATH : '/',
            'domain' => defined('SESSION_COOKIE_DOMAIN') ? (string) SESSION_COOKIE_DOMAIN : '',
            'secure' => $secureCookie,
            'httponly' => defined('SESSION_COOKIE_HTTP_ONLY') ? (bool) SESSION_COOKIE_HTTP_ONLY : true,
            'samesite' => defined('SESSION_COOKIE_SAMESITE') ? (string) SESSION_COOKIE_SAMESITE : 'Lax',
        ];

        session_set_cookie_params($cookieParams);
        // Read-only session to avoid locking long-poll and inbox requests together.
        session_start(['read_and_close' => true]);
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
        if (in_array($remoteAddr, ['127.0.0.1', '::1'], true)) {
            return true;
        }

        return false;
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

        $remote = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remote !== '') {
            return $remote;
        }

        return '';
    }

    private static function isIpAllowed(string $ip): bool
    {
        if (API_ALLOWED_IPS === '') {
            return true;
        }

        if ($ip === '') {
            return false;
        }

        $entries = array_filter(array_map('trim', explode(',', API_ALLOWED_IPS)));
        if (empty($entries)) {
            return true;
        }

        foreach ($entries as $entry) {
            if ($entry === $ip) {
                return true;
            }

            if (str_contains($entry, '/') && self::ipInCidr($ip, $entry)) {
                return true;
            }
        }

        return false;
    }

    private static function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $maskBits] = array_pad(explode('/', $cidr, 2), 2, null);
        if ($subnet === null || $maskBits === null) {
            return false;
        }

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        $maskBits = (int) $maskBits;

        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $bytesCount = strlen($ipBin);
        $fullBytes = intdiv($maskBits, 8);
        $remainingBits = $maskBits % 8;

        for ($i = 0; $i < $fullBytes; $i++) {
            if ($ipBin[$i] !== $subnetBin[$i]) {
                return false;
            }
        }

        if ($remainingBits > 0 && $fullBytes < $bytesCount) {
            $mask = (~((1 << (8 - $remainingBits)) - 1)) & 0xFF;
            if ((ord($ipBin[$fullBytes]) & $mask) !== (ord($subnetBin[$fullBytes]) & $mask)) {
                return false;
            }
        }

        return true;
    }

    private static function getRawBody(): string
    {
        if (self::$rawBody !== null) {
            return self::$rawBody;
        }

        self::$rawBody = (string) file_get_contents('php://input');
        $_SERVER['KAIMAIL_RAW_BODY'] = self::$rawBody;
        return self::$rawBody;
    }

    private static function isSameOriginRequest(): bool
    {
        $baseUrlParts = parse_url(BASE_URL);
        if (empty($baseUrlParts['scheme']) || empty($baseUrlParts['host'])) {
            return false;
        }

        $allowedOrigin = $baseUrlParts['scheme'] . '://' . $baseUrlParts['host'];
        if (!empty($baseUrlParts['port'])) {
            $allowedOrigin .= ':' . $baseUrlParts['port'];
        }
        $allowedOrigin = rtrim($allowedOrigin, '/');

        $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
        if ($origin !== '') {
            return rtrim($origin, '/') === $allowedOrigin;
        }

        $referer = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));
        if ($referer === '') {
            return true;
        }

        $refererParts = parse_url($referer);
        if (!is_array($refererParts) || empty($refererParts['scheme']) || empty($refererParts['host'])) {
            return false;
        }

        $refererOrigin = $refererParts['scheme'] . '://' . $refererParts['host'];
        if (!empty($refererParts['port'])) {
            $refererOrigin .= ':' . $refererParts['port'];
        }

        return rtrim($refererOrigin, '/') === $allowedOrigin;
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

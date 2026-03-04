<?php
declare(strict_types=1);

/**
 * Bảo mật API:
 * - UI admin: X-ADMIN-ACCESS-KEY
 * - Tool ngoài: X-API-KEY + X-API-SECRET + X-API-TIMESTAMP + X-API-SIGNATURE
 *
 * Không dùng cookie/session cho xác thực API.
 */
final class ApiSecurity
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
        header('Access-Control-Allow-Headers: Content-Type, X-ADMIN-ACCESS-KEY, X-API-KEY, X-API-SECRET, X-API-TIMESTAMP, X-API-SIGNATURE');
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

    public static function requireAdminOrApiAuth(): void
    {
        self::enforceNetworkPolicy();

        if (self::verifyAdminAccessKeyHeader()) {
            return;
        }

        self::requireApiAuth();
    }

    public static function requireApiAuth(): void
    {
        self::enforceNetworkPolicy();

        $apiKey = self::getHeader('X-API-KEY');
        $apiSecret = self::getHeader('X-API-SECRET');
        $timestampHeader = self::getHeader('X-API-TIMESTAMP');
        $signature = self::getHeader('X-API-SIGNATURE');

        if ($apiKey === '' || $apiSecret === '' || $timestampHeader === '' || $signature === '') {
            self::deny(401, 'Thiếu header xác thực API');
        }

        if (!hash_equals((string) API_ACCESS_KEY, $apiKey) || !hash_equals((string) API_SECRET_KEY, $apiSecret)) {
            self::deny(401, 'API key hoặc secret không hợp lệ');
        }

        $timestamp = (int) $timestampHeader;
        if ($timestamp <= 0) {
            self::deny(401, 'Timestamp không hợp lệ');
        }

        if (abs(time() - $timestamp) > API_REQUEST_TTL) {
            self::deny(401, 'Yêu cầu đã hết thời gian hợp lệ');
        }

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $payload = $method . "\n" . $path . "\n" . $timestamp;
        $expectedSignature = hash_hmac('sha256', $payload, (string) API_SECRET_KEY);

        if (!hash_equals($expectedSignature, $signature)) {
            self::deny(401, 'Chữ ký API không hợp lệ');
        }
    }

    public static function verifyAdminAccessKeyHeader(): bool
    {
        $adminAccessKey = self::getHeader('X-ADMIN-ACCESS-KEY');
        if ($adminAccessKey === '') {
            return false;
        }

        return hash_equals((string) ADMIN_ACCESS_KEY, $adminAccessKey);
    }

    private static function enforceNetworkPolicy(): void
    {
        if (API_REQUIRE_HTTPS && !self::isHttpsRequest()) {
            self::deny(403, 'Bắt buộc dùng HTTPS');
        }

        if (!self::isIpAllowed(self::getClientIp())) {
            self::deny(403, 'IP không được phép');
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

    private static function isHttpsRequest(): bool
    {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off') {
            return true;
        }

        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwardedProto === 'https') {
            return true;
        }

        return ((string) ($_SERVER['SERVER_PORT'] ?? '')) === '443';
    }

    private static function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            $value = trim((string) ($_SERVER[$header] ?? ''));
            if ($value === '') {
                continue;
            }

            if ($header === 'HTTP_X_FORWARDED_FOR') {
                $parts = array_map('trim', explode(',', $value));
                if (isset($parts[0]) && $parts[0] !== '') {
                    return $parts[0];
                }
            } else {
                return $value;
            }
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

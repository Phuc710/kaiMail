<?php

/**
 * Lightweight .env loader (no external dependency).
 * Existing OS/server environment variables take precedence.
 */
function loadEnvFile(string $filePath): void
{
    static $loaded = [];

    if (isset($loaded[$filePath]) || !is_file($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (str_starts_with($line, 'export ')) {
            $line = trim(substr($line, 7));
        }

        $separatorPos = strpos($line, '=');
        if ($separatorPos === false) {
            continue;
        }

        $name = trim(substr($line, 0, $separatorPos));
        $value = trim(substr($line, $separatorPos + 1));

        if ($name === '') {
            continue;
        }

        if (preg_match('/\A["\'].*["\']\z/', $value) === 1) {
            $value = substr($value, 1, -1);
        }

        if (getenv($name) !== false) {
            continue;
        }

        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        putenv($name . '=' . $value);
    }

    $loaded[$filePath] = true;
}

/**
 * Read an environment variable and normalize common scalar values.
 */
function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    $normalized = strtolower(trim((string) $value));

    return match ($normalized) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        'empty', '(empty)' => '',
        default => $value,
    };
}

/**
 * Read a required environment variable.
 */
function envRequired(string $key): string
{
    $value = env($key);

    if (!is_string($value) || trim($value) === '') {
        throw new RuntimeException("Missing required environment variable: {$key}");
    }

    return $value;
}

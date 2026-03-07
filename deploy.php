<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

/**
 * Production webhook deploy endpoint.
 *
 * Required .env:
 * - DEPLOY_SECRET (required)
 * - DEPLOY_BRANCH (optional, default: main)
 * - DEPLOY_REPO (optional but recommended, owner/repo)
 */

function deployRespond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function deployLoadEnvFile(string $envPath): void
{
    if (!is_file($envPath) || !is_readable($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES);
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

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $name = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        if ($name === '' || getenv($name) !== false) {
            continue;
        }

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        putenv($name . '=' . $value);
    }
}

function deployGetHeader(string $name): string
{
    $normalized = strtoupper(str_replace('-', '_', $name));
    $keys = [
        'HTTP_' . $normalized,
        $normalized,
    ];

    foreach ($keys as $key) {
        if (isset($_SERVER[$key])) {
            return trim((string) $_SERVER[$key]);
        }
    }

    if (function_exists('getallheaders')) {
        foreach ((array) getallheaders() as $headerName => $value) {
            $headerKey = strtoupper(str_replace('-', '_', (string) $headerName));
            if ($headerKey === $normalized) {
                return trim((string) $value);
            }
        }
    }

    return '';
}

function deployGetClientIp(): string
{
    $candidates = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($candidates as $key) {
        $value = trim((string) ($_SERVER[$key] ?? ''));
        if ($value === '') {
            continue;
        }
        if ($key === 'HTTP_X_FORWARDED_FOR') {
            $parts = array_map('trim', explode(',', $value));
            return (string) ($parts[0] ?? '');
        }
        return $value;
    }

    return '';
}

function deployEnsureParentDir(string $filePath): void
{
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

function deployLog(string $logFile, string $type, string $message, array $context = []): void
{
    deployEnsureParentDir($logFile);
    $timestamp = date('Y-m-d H:i:s');
    $contextJson = $context === [] ? '{}' : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $line = sprintf("[%s] [%s] %s %s\n", $timestamp, strtoupper($type), $message, $contextJson);
    @file_put_contents($logFile, $line, FILE_APPEND);
}

function deployBuildCommand(string $binary, array $args): string
{
    $parts = [escapeshellarg($binary)];
    foreach ($args as $arg) {
        $parts[] = escapeshellarg($arg);
    }
    return implode(' ', $parts);
}

function deployRunCommand(string $command): array
{
    $output = [];
    $exitCode = 1;
    exec($command . ' 2>&1', $output, $exitCode);

    return [
        'command' => $command,
        'exit_code' => $exitCode,
        'output' => trim(implode("\n", $output)),
    ];
}

$rootDir = __DIR__;
deployLoadEnvFile($rootDir . '/.env');

$logFile = $rootDir . '/storage/logs/deploy.log';
$requestId = trim((string) deployGetHeader('X-GitHub-Delivery'));
if ($requestId === '') {
    try {
        $requestId = bin2hex(random_bytes(8));
    } catch (Throwable $e) {
        $requestId = str_replace('.', '', uniqid('deploy_', true));
    }
}

$clientIp = deployGetClientIp();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$event = deployGetHeader('X-GitHub-Event');
$signature = deployGetHeader('X-Hub-Signature-256');

$baseContext = [
    'request_id' => $requestId,
    'ip' => $clientIp,
    'method' => $method,
    'event' => $event,
];

if ($method !== 'POST') {
    header('Allow: POST');
    deployLog($logFile, 'reject', 'method_not_allowed', $baseContext);
    deployRespond(405, ['ok' => false, 'message' => 'Method not allowed']);
}

$payloadRaw = file_get_contents('php://input');
if ($payloadRaw === false) {
    $payloadRaw = '';
}

$deploySecret = trim((string) (getenv('DEPLOY_SECRET') ?: ''));
if ($deploySecret === '') {
    deployLog($logFile, 'error', 'missing_deploy_secret', $baseContext);
    deployRespond(500, ['ok' => false, 'message' => 'DEPLOY_SECRET is required']);
}

if (!preg_match('/^sha256=[a-f0-9]{64}$/', $signature)) {
    deployLog($logFile, 'reject', 'missing_or_invalid_signature_header', $baseContext);
    deployRespond(401, ['ok' => false, 'message' => 'Invalid signature header']);
}

$expectedSignature = 'sha256=' . hash_hmac('sha256', $payloadRaw, $deploySecret);
if (!hash_equals($expectedSignature, $signature)) {
    deployLog($logFile, 'reject', 'signature_mismatch', $baseContext);
    deployRespond(401, ['ok' => false, 'message' => 'Signature mismatch']);
}

$payload = json_decode($payloadRaw, true);
if (!is_array($payload)) {
    deployLog($logFile, 'reject', 'invalid_json_payload', $baseContext);
    deployRespond(400, ['ok' => false, 'message' => 'Invalid JSON payload']);
}

if ($event !== 'push') {
    deployLog($logFile, 'ignore', 'ignored_non_push_event', $baseContext);
    deployRespond(202, ['ok' => true, 'message' => 'Ignored non-push event']);
}

$branch = trim((string) (getenv('DEPLOY_BRANCH') ?: 'main'));
if ($branch === '') {
    $branch = 'main';
}

$expectedRef = 'refs/heads/' . $branch;
$ref = (string) ($payload['ref'] ?? '');
if ($ref !== $expectedRef) {
    deployLog($logFile, 'ignore', 'ignored_wrong_branch', $baseContext + ['ref' => $ref, 'expected_ref' => $expectedRef]);
    deployRespond(202, ['ok' => true, 'message' => 'Ignored branch']);
}

$actualRepo = trim((string) ($payload['repository']['full_name'] ?? ''));
if ($actualRepo === '') {
    deployLog($logFile, 'reject', 'missing_repository_full_name', $baseContext);
    deployRespond(400, ['ok' => false, 'message' => 'Missing repository.full_name']);
}

$expectedRepo = trim((string) (getenv('DEPLOY_REPO') ?: ''));
if ($expectedRepo !== '' && strcasecmp($expectedRepo, $actualRepo) !== 0) {
    deployLog($logFile, 'ignore', 'ignored_wrong_repository', $baseContext + [
        'repo' => $actualRepo,
        'expected_repo' => $expectedRepo,
    ]);
    deployRespond(202, ['ok' => true, 'message' => 'Ignored repository']);
}

if ($expectedRepo === '') {
    deployLog($logFile, 'ok', 'deploy_repo_not_set', $baseContext + ['repo' => $actualRepo]);
}

$deployPath = trim((string) (getenv('DEPLOY_PATH') ?: $rootDir));
if ($deployPath === '' || !is_dir($deployPath)) {
    deployLog($logFile, 'error', 'deploy_path_not_found', $baseContext + ['deploy_path' => $deployPath]);
    deployRespond(500, ['ok' => false, 'message' => 'Deploy path not found']);
}

$lockFile = $rootDir . '/storage/locks/deploy.lock';
deployEnsureParentDir($lockFile);
$lockHandle = @fopen($lockFile, 'c');
if ($lockHandle === false) {
    deployLog($logFile, 'error', 'lock_file_open_failed', $baseContext + ['lock_file' => $lockFile]);
    deployRespond(500, ['ok' => false, 'message' => 'Unable to open lock file']);
}

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    deployLog($logFile, 'ignore', 'deploy_already_running', $baseContext);
    fclose($lockHandle);
    deployRespond(409, ['ok' => false, 'message' => 'Deploy already running']);
}

register_shutdown_function(static function () use ($lockHandle): void {
    @flock($lockHandle, LOCK_UN);
    @fclose($lockHandle);
});

$gitBin = trim((string) (getenv('GIT_BIN') ?: 'git'));
$remote = trim((string) (getenv('DEPLOY_REMOTE') ?: 'origin'));
if ($remote === '') {
    $remote = 'origin';
}

putenv('GIT_TERMINAL_PROMPT=0');

$commands = [
    deployBuildCommand($gitBin, ['-C', $deployPath, 'rev-parse', '--is-inside-work-tree']),
    deployBuildCommand($gitBin, ['-C', $deployPath, 'fetch', '--prune', $remote, $branch]),
    deployBuildCommand($gitBin, ['-C', $deployPath, 'checkout', $branch]),
    deployBuildCommand($gitBin, ['-C', $deployPath, 'pull', '--ff-only', $remote, $branch]),
];

$steps = [];
foreach ($commands as $command) {
    $result = deployRunCommand($command);
    $steps[] = $result;

    if ((int) $result['exit_code'] !== 0) {
        deployLog($logFile, 'error', 'deploy_command_failed', $baseContext + [
            'repo' => $actualRepo,
            'branch' => $branch,
            'step' => $result,
        ]);
        deployRespond(500, [
            'ok' => false,
            'message' => 'Deploy failed',
            'request_id' => $requestId,
        ]);
    }
}

$commitResult = deployRunCommand(
    deployBuildCommand($gitBin, ['-C', $deployPath, 'rev-parse', '--short', 'HEAD'])
);
$commit = (int) $commitResult['exit_code'] === 0 ? trim((string) $commitResult['output']) : '';

deployLog($logFile, 'ok', 'deploy_success', $baseContext + [
    'repo' => $actualRepo,
    'branch' => $branch,
    'commit' => $commit,
]);

deployRespond(200, [
    'ok' => true,
    'message' => 'Deploy completed',
    'request_id' => $requestId,
    'branch' => $branch,
    'repo' => $actualRepo,
    'commit' => $commit,
]);

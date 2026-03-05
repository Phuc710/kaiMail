<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function deployResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function deployLog(string $logFile, string $message): void
{
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
    @file_put_contents($logFile, $line, FILE_APPEND);
}

function runCommand(string $command): array
{
    $output = [];
    $exitCode = 1;
    exec($command, $output, $exitCode);

    return [
        'exit_code' => $exitCode,
        'output' => trim(implode("\n", $output)),
    ];
}

function buildCommand(string $binary, array $args): string
{
    $parts = [escapeshellarg($binary)];
    foreach ($args as $arg) {
        $parts[] = escapeshellarg($arg);
    }

    return implode(' ', $parts);
}

$rootDir = __DIR__;
$envLoader = $rootDir . '/config/env.php';
if (is_file($envLoader)) {
    require_once $envLoader;
    if (function_exists('loadEnvFile')) {
        loadEnvFile($rootDir . '/.env');
    }
}

$logFile = $rootDir . '/storage/logs/deploy.log';
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'POST') {
    header('Allow: POST');
    deployResponse(405, ['ok' => false, 'message' => 'Method not allowed']);
}

$payloadRaw = file_get_contents('php://input');
if ($payloadRaw === false) {
    $payloadRaw = '';
}

$deploySecret = trim((string) (getenv('DEPLOY_SECRET') ?: ''));
if ($deploySecret === '') {
    $deploySecret = trim((string) (getenv('WEBHOOK_SECRET') ?: ''));
}

if ($deploySecret === '') {
    deployLog($logFile, 'Missing DEPLOY_SECRET/WEBHOOK_SECRET');
    deployResponse(500, ['ok' => false, 'message' => 'Missing deployment secret on server']);
}

$signature = trim((string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? ''));
if ($signature === '' || !str_starts_with($signature, 'sha256=')) {
    deployLog($logFile, 'Missing or invalid signature header');
    deployResponse(401, ['ok' => false, 'message' => 'Invalid signature']);
}

$expectedSignature = 'sha256=' . hash_hmac('sha256', $payloadRaw, $deploySecret);
if (!hash_equals($expectedSignature, $signature)) {
    deployLog($logFile, 'Signature mismatch');
    deployResponse(401, ['ok' => false, 'message' => 'Signature mismatch']);
}

$event = (string) ($_SERVER['HTTP_X_GITHUB_EVENT'] ?? '');
$payload = json_decode($payloadRaw, true);
if (!is_array($payload)) {
    deployResponse(400, ['ok' => false, 'message' => 'Invalid JSON payload']);
}

if ($event === 'ping') {
    deployResponse(200, ['ok' => true, 'message' => 'pong']);
}

if ($event !== 'push') {
    deployResponse(202, ['ok' => true, 'message' => 'Ignored non-push event']);
}

$branch = trim((string) (getenv('DEPLOY_BRANCH') ?: 'main'));
$expectedRef = 'refs/heads/' . $branch;
$ref = (string) ($payload['ref'] ?? '');
if ($ref !== $expectedRef) {
    deployLog($logFile, "Ignored push to {$ref}");
    deployResponse(202, ['ok' => true, 'message' => "Ignored branch {$ref}"]);
}

$expectedRepository = trim((string) (getenv('DEPLOY_REPOSITORY') ?: ''));
$actualRepository = (string) ($payload['repository']['full_name'] ?? '');
if ($expectedRepository !== '' && strcasecmp($expectedRepository, $actualRepository) !== 0) {
    deployLog($logFile, "Ignored repository {$actualRepository}");
    deployResponse(202, ['ok' => true, 'message' => 'Ignored repository']);
}

$deployPath = trim((string) (getenv('DEPLOY_PATH') ?: $rootDir));
if ($deployPath === '') {
    $deployPath = $rootDir;
}

if (!is_dir($deployPath)) {
    deployLog($logFile, "Deploy path not found: {$deployPath}");
    deployResponse(500, ['ok' => false, 'message' => 'Deploy path not found']);
}

$lockFile = $rootDir . '/storage/locks/deploy.lock';
$lockDir = dirname($lockFile);
if (!is_dir($lockDir)) {
    @mkdir($lockDir, 0775, true);
}

$lockHandle = @fopen($lockFile, 'c');
if ($lockHandle === false) {
    deployResponse(500, ['ok' => false, 'message' => 'Unable to create lock file']);
}

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    deployResponse(409, ['ok' => false, 'message' => 'Deploy already running']);
}

$gitBin = trim((string) (getenv('GIT_BIN') ?: 'git'));
$remote = trim((string) (getenv('DEPLOY_REMOTE') ?: 'origin'));
$commands = [
    buildCommand($gitBin, ['-C', $deployPath, 'rev-parse', '--is-inside-work-tree']) . ' 2>&1',
    buildCommand($gitBin, ['-C', $deployPath, 'pull', '--ff-only', $remote, $branch]) . ' 2>&1',
];

$steps = [];
foreach ($commands as $command) {
    $result = runCommand($command);
    $steps[] = [
        'command' => $command,
        'exit_code' => $result['exit_code'],
        'output' => $result['output'],
    ];

    if ($result['exit_code'] !== 0) {
        deployLog($logFile, 'Deploy failed: ' . $result['output']);
        deployResponse(500, [
            'ok' => false,
            'message' => 'Deploy failed',
            'steps' => $steps,
        ]);
    }
}

$commitResult = runCommand(
    buildCommand($gitBin, ['-C', $deployPath, 'rev-parse', '--short', 'HEAD']) . ' 2>&1'
);
$commit = $commitResult['exit_code'] === 0 ? $commitResult['output'] : null;

deployLog(
    $logFile,
    sprintf(
        'Deploy success repo=%s branch=%s commit=%s',
        $actualRepository !== '' ? $actualRepository : '-',
        $branch,
        $commit !== null ? $commit : '-'
    )
);

deployResponse(200, [
    'ok' => true,
    'message' => 'Deploy completed',
    'branch' => $branch,
    'commit' => $commit,
]);

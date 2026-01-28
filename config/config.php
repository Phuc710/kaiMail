<?php

$hostname = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('ENVIRONMENT', getenv('APP_ENV') ?: ($hostname === 'tmail.kaishop.id.vn' ? 'production' : 'development'));

// ======================
// APPLICATION SETTINGS
// ======================
$config = [
    'development' => [
        'base_url' => 'http://localhost/kaiMail',
        'production_domain' => '',  // Not used in dev
    ],
    'production' => [
        'base_url' => 'https://tmail.kaishop.id.vn',
        'production_domain' => 'tmail.kaishop.id.vn',
    ],
];

define('BASE_URL', $config[ENVIRONMENT]['base_url']);
define('PRODUCTION_DOMAIN', $config[ENVIRONMENT]['production_domain'] ?? '');

// DATABASE CONFIGURATION
// ======================
if (ENVIRONMENT === 'production') {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'kaishopi_tmail');
    define('DB_USER', getenv('DB_USER') ?: 'kaishopi_tmail');
    define('DB_PASS', getenv('DB_PASS') ?: 'YsRdaEMbhSDSaRgUd6rb');
} else {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'kaimail');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
}
define('DB_CHARSET', 'utf8mb4');

// ======================
// WEBHOOK & SECURITY
// ======================
define('WEBHOOK_SECRET', getenv('WEBHOOK_SECRET') ?: '65a276de438f97d2b4496724e59d18d443168d3d2ed');

// ======================
// SESSION SETTINGS
// ======================
define('SESSION_NAME', 'kaimail_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// ======================
// TIMEZONE
// ======================
date_default_timezone_set('Asia/Ho_Chi_Minh');

// ======================
// ERROR REPORTING
// ======================
error_reporting(E_ALL);
ini_set('display_errors', ENVIRONMENT === 'development' ? '1' : '0');

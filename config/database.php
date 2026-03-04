<?php

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}


/**
 * Get PDO Database Connection
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            $dbTimezone = DB_TIMEZONE;
            if (preg_match('/^[+-](0\d|1[0-4]):[0-5]\d$/', $dbTimezone) !== 1) {
                $dbTimezone = '+00:00';
            }

            $pdo->exec("SET time_zone = '{$dbTimezone}';");
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            $response = ['error' => 'Database connection failed'];
            if (EXPOSE_ERROR_DETAILS) {
                $response['message'] = $e->getMessage();
            }
            die(json_encode($response));
        }
    }

    return $pdo;
}

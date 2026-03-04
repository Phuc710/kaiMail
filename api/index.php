<?php
/**
 * KaiMail API Router
 * Clean RESTful API with proper routing
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/middleware/ApiSecurity.php';
require_once __DIR__ . '/services/BaseService.php';
require_once __DIR__ . '/services/DomainService.php';
require_once __DIR__ . '/services/EmailService.php';
require_once __DIR__ . '/controllers/EmailController.php';

ApiSecurity::setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');
ApiSecurity::handlePreflight();

try {
    ApiSecurity::requireApiAuth();

    // Get request info
    $method = $_SERVER['REQUEST_METHOD'];
    $requestUri = $_SERVER['REQUEST_URI'];

    // Parse path - remove base path and query string
    $basePath = '/kaiMail/api';
    if (strpos($requestUri, $basePath) === 0) {
        $requestUri = substr($requestUri, strlen($basePath));
    }

    // Remove query string
    $path = strtok($requestUri, '?');
    $path = trim($path, '/');

    // Split path into segments
    $segments = $path ? explode('/', $path) : [];

    // Initialize services
    $db = getDB();
    $domainService = new DomainService($db);
    $emailService = new EmailService($db);
    $emailController = new EmailController($emailService, $domainService);

    // Route handling
    if (empty($segments)) {
        // GET /api - API info
        if ($method === 'GET') {
            jsonResponse([
                'name' => 'KaiMail API',
                'version' => '2.0',
                'endpoints' => [
                    'POST /api/emails' => 'Create new email(s)',
                    'GET /api/emails/{email}/messages' => 'Get messages for email'
                ]
            ]);
        } else {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
    }

    // Route: /emails
    if ($segments[0] === 'emails') {

        // POST /api/emails - Create email
        if ($method === 'POST' && count($segments) === 1) {
            $emailController->create();
            exit;
        }

        // GET /api/emails/{email}/messages - Get messages
        if ($method === 'GET' && count($segments) === 3 && $segments[2] === 'messages') {
            $email = urldecode($segments[1]);
            $emailController->getMessages($email);
            exit;
        }
    }

    // No route matched
    jsonResponse([
        'error' => 'Not found',
        'path' => '/' . $path,
        'method' => $method
    ], 404);

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    $message = 'An error occurred';
    if (EXPOSE_ERROR_DETAILS) {
        $message = $e->getMessage();
    }
    jsonResponse([
        'error' => 'Internal server error',
        'message' => $message
    ], 500);
}

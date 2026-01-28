<?php
/**
 * Authentication Middleware
 * Validates X-Secret-Key header for external API access
 */

class AuthMiddleware
{
    /**
     * Validate secret key from request header
     * 
     * @throws Exception if authentication fails
     */
    public static function authenticate(): void
    {
        $secretKey = $_SERVER['HTTP_X_SECRET_KEY'] ?? '';

        if (empty($secretKey)) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Unauthorized',
                'message' => 'X-Secret-Key header is required'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($secretKey !== WEBHOOK_SECRET) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Unauthorized',
                'message' => 'Invalid secret key'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

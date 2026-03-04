<?php
/**
 * Backward-compatible authentication wrapper.
 */

class AuthMiddleware
{
    public static function authenticate(): void
    {
        require_once __DIR__ . '/ApiSecurity.php';
        ApiSecurity::requireApiAuth();
    }
}

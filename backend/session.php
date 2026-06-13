<?php
// backend/session.php
// Centralized session bootstrap + CSRF token helper (no database dependency).
// Included by backend endpoints (via config.php) and by frontend page guards,
// so session cookie configuration lives in exactly one place.

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == '443');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,      // not readable from JavaScript
        'secure'   => $isHttps,  // only sent over HTTPS when available
        'samesite' => 'Lax',     // mitigates cross-site request forgery
    ]);

    session_start();
}

if (!function_exists('csrf_token')) {
    /** Return the per-session CSRF token, creating it on first use. */
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

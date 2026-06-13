<?php
// backend/logout.php
// Fully tears down the session and redirects to the login page.

require_once __DIR__ . '/session.php';

// Clear session data, expire the session cookie, then destroy the session.
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: ../frontend/login.php');
exit;

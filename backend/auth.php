<?php
// backend/auth.php
// JSON response, authentication/authorization, and CSRF helpers for backend
// endpoints. Endpoints include this instead of config.php; it pulls in config.php
// (DB + session) and standardizes how access control and responses are handled.

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

/** Send a JSON payload with an HTTP status and stop execution. */
function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

/** Require an authenticated session; returns the current user id. */
function require_login(): int
{
    if (empty($_SESSION['user_id'])) {
        json_response(['success' => false, 'message' => 'Not logged in.'], 401);
    }
    return (int) $_SESSION['user_id'];
}

/** Require the logged-in user to hold one of the given roles; returns user id. */
function require_role(string ...$roles): int
{
    $id = require_login();
    if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
        json_response(['success' => false, 'message' => 'Access denied.'], 403);
    }
    return $id;
}

/** Require the request to be a POST. */
function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
    }
}

/**
 * Verify the CSRF token on a state-changing request. The token is accepted from
 * the X-CSRF-Token header (sent automatically by frontend/assets/app.js) or a
 * csrf_token POST field as a fallback.
 */
function require_csrf(): void
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (empty($_SESSION['csrf_token']) || !is_string($sent) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        json_response(['success' => false, 'message' => 'Your session token is invalid or expired. Please refresh the page and try again.'], 419);
    }
}

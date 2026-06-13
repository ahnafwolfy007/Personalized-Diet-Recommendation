<?php
// frontend/guard.php
// Server-side authentication & role guard for protected pages. Include at the
// very top of a page (before any output) and call guard(...allowed roles).
// This is the authoritative access check; the client-side redirects in each
// page are only a secondary fallback.

require_once __DIR__ . '/../backend/session.php';

if (!function_exists('guard')) {
    /**
     * Ensure the visitor is logged in and (optionally) holds one of the given
     * roles. Redirects away when the check fails. Returns basic session info.
     */
    function guard(string ...$roles): array
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        $role = $_SESSION['user_role'] ?? '';
        if (!empty($roles) && !in_array($role, $roles, true)) {
            // Logged in but wrong role: send them to their own home, not login.
            $home = $role === 'dietitian' ? 'dietitian-dashboard.php'
                  : ($role === 'admin' ? 'admin-dashboard.php' : 'user-dashboard.php');
            header('Location: ' . $home);
            exit;
        }

        return [
            'id'   => (int) $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? '',
            'role' => $role,
        ];
    }
}

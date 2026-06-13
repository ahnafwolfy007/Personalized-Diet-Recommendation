<?php
// backend/admin_get_users.php
// Returns all non-admin users for the admin user management table

require_once __DIR__ . '/auth.php';

require_role('admin');

$result = $conn->query("SELECT user_id, name, email, role, status, created_at FROM users WHERE role != 'admin' ORDER BY created_at DESC");

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

json_response(['success' => true, 'users' => $users]);

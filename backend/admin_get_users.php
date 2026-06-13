<?php
// backend/admin_get_users.php
// Returns one page of non-admin users for the admin user-management table.
// Supports an optional ?q= search (name or email) and ?page= pagination.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

require_role('admin');

[$page, $perPage, $offset] = pagination_args(15);
$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

if ($q !== '') {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM users WHERE role != 'admin' AND (name LIKE ? OR email LIKE ?)");
    $countStmt->bind_param('ss', $like, $like);
    $countStmt->execute();
    $total = (int) $countStmt->get_result()->fetch_assoc()['c'];
    $countStmt->close();

    $stmt = $conn->prepare("
        SELECT user_id, name, email, role, status, created_at
        FROM users
        WHERE role != 'admin' AND (name LIKE ? OR email LIKE ?)
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('ssii', $like, $like, $perPage, $offset);
} else {
    $total = (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE role != 'admin'")->fetch_assoc()['c'];

    $stmt = $conn->prepare("
        SELECT user_id, name, email, role, status, created_at
        FROM users
        WHERE role != 'admin'
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('ii', $perPage, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

json_response([
    'success'    => true,
    'users'      => $users,
    'pagination' => pagination_meta($total, $page, $perPage),
]);

<?php
// backend/admin_delete_user.php
// Deletes a user (admin only)

require_once __DIR__ . '/auth.php';

require_role('admin');
require_post();
require_csrf();

$target_id = intval($_POST['user_id'] ?? 0);

if ($target_id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid user ID.']);
}

// Never allow deleting admin accounts.
$stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role != 'admin'");
$stmt->bind_param('i', $target_id);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected > 0) {
    json_response(['success' => true, 'message' => 'User deleted.']);
} else {
    json_response(['success' => false, 'message' => 'User not found or cannot be deleted.'], 404);
}

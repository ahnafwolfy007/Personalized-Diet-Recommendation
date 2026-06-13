<?php
// backend/delete_food_log.php
// Deletes one of the logged-in patient's own food log entries.

require_once __DIR__ . '/auth.php';

$user_id = require_login();
require_post();
require_csrf();

$log_id = intval($_POST['log_id'] ?? 0);

if ($log_id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid log entry.']);
}

// Ownership is enforced in the WHERE clause: a user can only delete their own logs.
$stmt = $conn->prepare("DELETE FROM food_logs WHERE log_id = ? AND user_id = ?");
$stmt->bind_param('ii', $log_id, $user_id);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected > 0) {
    json_response(['success' => true, 'message' => 'Entry removed.']);
} else {
    json_response(['success' => false, 'message' => 'Entry not found.'], 404);
}

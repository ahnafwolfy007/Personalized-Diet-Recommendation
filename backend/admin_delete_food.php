<?php
// backend/admin_delete_food.php
// Admin removes a food. Related food logs and plan items cascade-delete via FK.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$admin_id = require_role('admin');
require_post();
require_csrf();

$food_id = intval($_POST['food_id'] ?? 0);
if ($food_id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid food.']);
}

$stmt = $conn->prepare("DELETE FROM foods WHERE food_id = ?");
$stmt->bind_param('i', $food_id);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected > 0) {
    log_activity($conn, $admin_id, 'admin', 'food_delete', 'Deleted food #' . $food_id);
    json_response(['success' => true, 'message' => 'Food deleted.']);
}
json_response(['success' => false, 'message' => 'Food not found.'], 404);

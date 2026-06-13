<?php
// backend/delete_water.php
// Removes one of the logged-in patient's own water entries.

require_once __DIR__ . '/auth.php';

$user_id = require_role('patient');
require_post();
require_csrf();

$water_id = intval($_POST['water_id'] ?? 0);
if ($water_id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid entry.']);
}

// Ownership enforced in the WHERE clause.
$stmt = $conn->prepare("DELETE FROM water_logs WHERE water_id = ? AND user_id = ?");
$stmt->bind_param('ii', $water_id, $user_id);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected > 0) {
    json_response(['success' => true, 'message' => 'Entry removed.']);
}
json_response(['success' => false, 'message' => 'Entry not found.'], 404);

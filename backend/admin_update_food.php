<?php
// backend/admin_update_food.php
// Admin edits a food's nutritional info and/or verifies it.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$admin_id = require_role('admin');
require_post();
require_csrf();

$food_id     = intval($_POST['food_id'] ?? 0);
$name        = trim($_POST['name'] ?? '');
$category    = trim($_POST['category'] ?? 'General');
$calories    = floatval($_POST['calories_per_100g'] ?? 0);
$is_verified = intval($_POST['is_verified'] ?? 0) === 1 ? 1 : 0;

if ($food_id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid food.']);
}
if ($name === '' || mb_strlen($name) > 150) {
    json_response(['success' => false, 'message' => 'Enter a valid food name.']);
}
if ($calories <= 0 || $calories > 1000) {
    json_response(['success' => false, 'message' => 'Calories per 100g must be between 1 and 1000.']);
}
if ($category === '' || mb_strlen($category) > 50) {
    $category = 'General';
}

$stmt = $conn->prepare("UPDATE foods SET name = ?, category = ?, calories_per_100g = ?, is_verified = ? WHERE food_id = ?");
$stmt->bind_param('ssdii', $name, $category, $calories, $is_verified, $food_id);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

// affected_rows is 0 when nothing changed; treat "exists" as success.
$exists = true;
if ($affected === 0) {
    $c = $conn->prepare("SELECT food_id FROM foods WHERE food_id = ?");
    $c->bind_param('i', $food_id);
    $c->execute();
    $exists = (bool) $c->get_result()->fetch_assoc();
    $c->close();
}

if (!$exists) {
    json_response(['success' => false, 'message' => 'Food not found.'], 404);
}

log_activity($conn, $admin_id, 'admin', 'food_update',
    ($is_verified ? 'Verified' : 'Updated') . ' food "' . mb_substr($name, 0, 80) . '"');

json_response(['success' => true, 'message' => $is_verified ? 'Food verified.' : 'Food updated.']);

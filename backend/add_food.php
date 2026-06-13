<?php
// backend/add_food.php
// Lets any logged-in user contribute a missing food by supplying calories per
// 100g. The food is stored unverified (is_verified = 0) and attributed to the
// author (created_by) until an admin reviews it.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user_id = require_login();
$role    = $_SESSION['user_role'] ?? '';
require_post();
require_csrf();

$name     = trim($_POST['name'] ?? '');
$calories = floatval($_POST['calories_per_100g'] ?? 0);
$category = trim($_POST['category'] ?? 'General');

if ($name === '' || mb_strlen($name) > 150) {
    json_response(['success' => false, 'message' => 'Please enter a valid food name (up to 150 characters).']);
}
if ($calories <= 0 || $calories > 1000) {
    json_response(['success' => false, 'message' => 'Calories per 100g must be between 1 and 1000.']);
}
if ($category === '' || mb_strlen($category) > 50) {
    $category = 'General';
}

// Dedupe on name (case-insensitive) so we don't pile up duplicates.
$stmt = $conn->prepare("SELECT food_id, is_verified FROM foods WHERE LOWER(name) = LOWER(?) LIMIT 1");
$stmt->bind_param('s', $name);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    json_response(['success' => false, 'message' => 'That food already exists in the database.']);
}

$stmt = $conn->prepare(
    "INSERT INTO foods (name, calories_per_100g, category, created_by, is_verified)
     VALUES (?, ?, ?, ?, 0)"
);
$stmt->bind_param('sdsi', $name, $calories, $category, $user_id);
$ok = $stmt->execute();
$new_id = $stmt->insert_id;
$stmt->close();

if (!$ok) {
    error_log('add_food failed: ' . $conn->error);
    json_response(['success' => false, 'message' => 'Could not add the food. Please try again.'], 500);
}

log_activity($conn, $user_id, $role, 'add_food', 'Added food "' . mb_substr($name, 0, 80) . '" (pending verification)');

json_response([
    'success' => true,
    'message' => 'Food added! It will be reviewed by an admin, but you can use it right away.',
    'food'    => [
        'food_id'           => (int) $new_id,
        'name'              => $name,
        'calories_per_100g' => $calories,
        'category'          => $category,
        'is_verified'       => 0,
    ],
]);

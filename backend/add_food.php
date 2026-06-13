<?php
// backend/add_food.php
// Contributes a food. The user enters how many calories a given amount has (in
// any household unit); the backend converts that amount to grams and normalizes
// to calories-per-100g before storing — the client never sends the per-100g value.
// Foods added by a regular user are stored unverified (is_verified=0) and
// attributed to them; foods added by an admin are verified immediately.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user_id = require_login();
$role    = $_SESSION['user_role'] ?? '';
require_post();
require_csrf();

$name     = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? 'General');
$unit     = trim($_POST['serving_unit'] ?? 'g');
$amount   = floatval($_POST['serving_amount'] ?? 0);
$calories = floatval($_POST['calories'] ?? 0); // calories for the given amount

if ($name === '' || mb_strlen($name) > 150) {
    json_response(['success' => false, 'message' => 'Please enter a valid food name (up to 150 characters).']);
}
// Category must be one of the known options.
if (!in_array($category, food_categories(), true)) {
    $category = 'General';
}
if (!array_key_exists($unit, serving_units())) {
    $unit = 'g';
}
if ($amount <= 0) {
    json_response(['success' => false, 'message' => 'Enter a valid amount.']);
}
if ($calories <= 0) {
    json_response(['success' => false, 'message' => 'Enter the calories for that amount.']);
}

// Normalize to calories per 100g, computed server-side.
$grams = serving_to_grams($unit, $amount);
if ($grams <= 0) {
    json_response(['success' => false, 'message' => 'Enter a valid amount.']);
}
$calories_per_100g = round(($calories / $grams) * 100, 1);
if ($calories_per_100g <= 0 || $calories_per_100g > 1000) {
    json_response(['success' => false, 'message' => 'That works out to ' . $calories_per_100g . ' kcal/100g, which is out of range (1–1000). Please check the amount and calories.']);
}

// Dedupe on name (case-insensitive).
$stmt = $conn->prepare("SELECT food_id FROM foods WHERE LOWER(name) = LOWER(?) LIMIT 1");
$stmt->bind_param('s', $name);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($existing) {
    json_response(['success' => false, 'message' => 'That food already exists in the database.']);
}

// Admin contributions are trusted/verified immediately.
$is_verified = ($role === 'admin') ? 1 : 0;

$stmt = $conn->prepare(
    "INSERT INTO foods (name, calories_per_100g, category, created_by, is_verified)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param('sdsii', $name, $calories_per_100g, $category, $user_id, $is_verified);
$ok = $stmt->execute();
$new_id = $stmt->insert_id;
$stmt->close();

if (!$ok) {
    error_log('add_food failed: ' . $conn->error);
    json_response(['success' => false, 'message' => 'Could not add the food. Please try again.'], 500);
}

log_activity($conn, $user_id, $role, 'add_food',
    'Added food "' . mb_substr($name, 0, 80) . '" (' . $calories_per_100g . ' kcal/100g' . ($is_verified ? '' : ', pending verification') . ')');

json_response([
    'success' => true,
    'message' => $is_verified
        ? 'Food added and verified.'
        : 'Food added! It will be reviewed by an admin, but you can use it right away.',
    'food'    => [
        'food_id'           => (int) $new_id,
        'name'              => $name,
        'calories_per_100g' => $calories_per_100g,
        'category'          => $category,
        'is_verified'       => $is_verified,
    ],
]);

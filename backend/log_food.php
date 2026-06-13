<?php
// backend/log_food.php
// Saves a food log entry for the logged-in patient. The user may enter the
// amount in grams or a household measurement (Portion, Glass, Tea-spoon,
// Table-spoon); the unit→grams conversion and the calorie math are both done
// server-side so the client can never spoof the stored calories.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user_id = require_login();
require_post();
require_csrf();

$food_id = intval($_POST['food_id'] ?? 0);

// New unit-aware inputs, with a backward-compatible fallback to a raw grams field.
$unit   = trim($_POST['serving_unit'] ?? 'g');
$amount = isset($_POST['serving_amount'])
    ? floatval($_POST['serving_amount'])
    : floatval($_POST['quantity'] ?? 0);

if (!array_key_exists($unit, serving_units())) {
    $unit = 'g';
}

if ($food_id <= 0 || $amount <= 0) {
    json_response(['success' => false, 'message' => 'Invalid food or amount.']);
}

// Resolve the amount to grams using the fixed standard values.
$quantity_g = round(serving_to_grams($unit, $amount), 1);
if ($quantity_g <= 0) {
    json_response(['success' => false, 'message' => 'Invalid amount.']);
}

// Get calories per 100g for the selected food.
$stmt = $conn->prepare("SELECT calories_per_100g FROM foods WHERE food_id = ?");
$stmt->bind_param('i', $food_id);
$stmt->execute();
$food = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$food) {
    json_response(['success' => false, 'message' => 'Food not found.'], 404);
}

$calories = round(($food['calories_per_100g'] / 100) * $quantity_g, 1);

$stmt = $conn->prepare(
    "INSERT INTO food_logs (user_id, food_id, quantity_g, calories_consumed, serving_unit, serving_amount)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('iiddsd', $user_id, $food_id, $quantity_g, $calories, $unit, $amount);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    json_response(['success' => true, 'calories' => $calories, 'quantity_g' => $quantity_g]);
}
error_log('log_food failed: ' . $conn->error);
json_response(['success' => false, 'message' => 'Failed to save log.'], 500);

<?php
// backend/log_food.php
// Saves a food log entry for the logged-in patient

require_once __DIR__ . '/auth.php';

$user_id = require_login();
require_post();
require_csrf();

$food_id  = intval($_POST['food_id'] ?? 0);
$quantity = floatval($_POST['quantity'] ?? 0);

if ($food_id <= 0 || $quantity <= 0) {
    json_response(['success' => false, 'message' => 'Invalid food or quantity.']);
}

// Get calories per 100g for the selected food
$stmt = $conn->prepare("SELECT calories_per_100g FROM foods WHERE food_id = ?");
$stmt->bind_param('i', $food_id);
$stmt->execute();
$food = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$food) {
    json_response(['success' => false, 'message' => 'Food not found.'], 404);
}

// Calculate calories for the given quantity
$calories = round(($food['calories_per_100g'] / 100) * $quantity, 1);

$stmt = $conn->prepare("INSERT INTO food_logs (user_id, food_id, quantity_g, calories_consumed) VALUES (?, ?, ?, ?)");
$stmt->bind_param('iidd', $user_id, $food_id, $quantity, $calories);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    json_response(['success' => true, 'calories' => $calories]);
} else {
    json_response(['success' => false, 'message' => 'Failed to save log.'], 500);
}

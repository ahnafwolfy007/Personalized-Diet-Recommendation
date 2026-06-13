<?php
// backend/log_water.php
// Logs a water-intake entry for the logged-in patient. Water lives in the same
// food_logs table as food (entry_type='water', food_id NULL, calories 0,
// quantity_g holds the millilitres). Water is NOT recorded in the admin
// Activity Monitor.

require_once __DIR__ . '/auth.php';

$user_id = require_role('patient');
require_post();
require_csrf();

$amount = intval($_POST['amount_ml'] ?? 0);

if ($amount <= 0 || $amount > 5000) {
    json_response(['success' => false, 'message' => 'Enter an amount between 1 and 5000 ml.']);
}

// logged_at from PHP so it matches the day_bounds() the "today" views use.
$now    = date('Y-m-d H:i:s');
$amountF = (float) $amount;

$stmt = $conn->prepare(
    "INSERT INTO food_logs (user_id, food_id, entry_type, quantity_g, calories_consumed, serving_unit, serving_amount, logged_at)
     VALUES (?, NULL, 'water', ?, 0, 'ml', ?, ?)"
);
$stmt->bind_param('idds', $user_id, $amountF, $amountF, $now);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    json_response(['success' => true, 'message' => 'Added ' . $amount . ' ml.']);
}
error_log('log_water failed: ' . $conn->error);
json_response(['success' => false, 'message' => 'Could not log water. Please try again.'], 500);

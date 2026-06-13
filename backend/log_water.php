<?php
// backend/log_water.php
// Logs a water-intake entry for the logged-in patient. Quick-add buttons send a
// preset amount; a custom field allows any value within a sane range. Water
// logging is intentionally NOT recorded in the admin Activity Monitor.

require_once __DIR__ . '/auth.php';

$user_id = require_role('patient');
require_post();
require_csrf();

$amount = intval($_POST['amount_ml'] ?? 0);

if ($amount <= 0 || $amount > 5000) {
    json_response(['success' => false, 'message' => 'Enter an amount between 1 and 5000 ml.']);
}

$stmt = $conn->prepare("INSERT INTO water_logs (user_id, amount_ml) VALUES (?, ?)");
$stmt->bind_param('ii', $user_id, $amount);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    json_response(['success' => true, 'message' => 'Added ' . $amount . ' ml.']);
}
error_log('log_water failed: ' . $conn->error);
json_response(['success' => false, 'message' => 'Could not log water. Please try again.'], 500);

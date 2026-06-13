<?php
// backend/log_plan_item.php
// Ticks (or un-ticks) a diet-plan item as "Taken" for today. Ticking auto-logs
// the item straight into the patient's food log (food_logs.plan_item_id set) so
// every entry lives in one place; un-ticking removes today's auto-log. Idempotent
// per item per day (we check for an existing row for today before inserting).
// SECURITY: the item must belong to a plan that targets this patient.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$patient_id = require_role('patient');
require_post();
require_csrf();

$item_id = intval($_POST['item_id'] ?? 0);
$action  = trim($_POST['action'] ?? 'take'); // 'take' | 'untake'

if ($item_id <= 0 || !in_array($action, ['take', 'untake'], true)) {
    json_response(['success' => false, 'message' => 'Invalid request.']);
}

// Verify ownership: the item's plan must belong to this patient.
$stmt = $conn->prepare("
    SELECT i.item_id, i.food_id, i.quantity_g, i.calories, i.serving_unit, i.serving_amount
    FROM diet_plan_items i
    JOIN diet_plans p ON p.plan_id = i.plan_id
    WHERE i.item_id = ? AND p.patient_id = ?
");
$stmt->bind_param('ii', $item_id, $patient_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    json_response(['success' => false, 'message' => 'Plan item not found.'], 404);
}

[$start, $end] = day_bounds(date('Y-m-d'));

// Is it already logged for today?
$stmt = $conn->prepare("
    SELECT log_id FROM food_logs
    WHERE plan_item_id = ? AND user_id = ? AND logged_at >= ? AND logged_at < ?
    LIMIT 1
");
$stmt->bind_param('iiss', $item_id, $patient_id, $start, $end);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($action === 'take') {
    if ($existing) {
        json_response(['success' => true, 'taken' => true, 'message' => 'Already logged for today.']);
    }

    $now    = date('Y-m-d H:i:s');
    $unit   = $item['serving_unit'] ?? 'g';
    $amount = $item['serving_amount'] !== null ? (float) $item['serving_amount'] : null;

    $stmt = $conn->prepare(
        "INSERT INTO food_logs (user_id, food_id, entry_type, quantity_g, calories_consumed, serving_unit, serving_amount, plan_item_id, logged_at)
         VALUES (?, ?, 'food', ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('iiddsdis', $patient_id, $item['food_id'], $item['quantity_g'], $item['calories'], $unit, $amount, $item_id, $now);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        error_log('log_plan_item take failed: ' . $conn->error);
        json_response(['success' => false, 'message' => 'Could not log this meal. Please try again.'], 500);
    }
    json_response(['success' => true, 'taken' => true, 'message' => 'Logged to your meal log.']);
}

// action === 'untake' — remove today's auto-logged row for this item.
if (!$existing) {
    json_response(['success' => true, 'taken' => false, 'message' => 'Not logged.']);
}

$stmt = $conn->prepare("
    DELETE FROM food_logs
    WHERE plan_item_id = ? AND user_id = ? AND logged_at >= ? AND logged_at < ?
");
$stmt->bind_param('iiss', $item_id, $patient_id, $start, $end);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    error_log('log_plan_item untake failed: ' . $conn->error);
    json_response(['success' => false, 'message' => 'Could not update this meal. Please try again.'], 500);
}
json_response(['success' => true, 'taken' => false, 'message' => 'Removed from your meal log.']);

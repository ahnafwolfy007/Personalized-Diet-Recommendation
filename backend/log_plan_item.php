<?php
// backend/log_plan_item.php
// Ticks (or un-ticks) a diet-plan item as "Taken" for today. Ticking auto-logs
// the item into the patient's food log so they don't have to add it manually;
// un-ticking removes today's auto-log. Idempotent per item per day via the
// UNIQUE (item_id, completed_on) constraint on meal_completions.
// SECURITY: the item must belong to a plan that targets this patient.

require_once __DIR__ . '/auth.php';

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
    SELECT i.item_id, i.food_id, i.quantity_g, i.calories
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

$today = date('Y-m-d');

// Current completion for today, if any.
$stmt = $conn->prepare("SELECT completion_id, log_id FROM meal_completions WHERE item_id = ? AND completed_on = ?");
$stmt->bind_param('is', $item_id, $today);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($action === 'take') {
    if ($existing) {
        json_response(['success' => true, 'taken' => true, 'message' => 'Already logged for today.']);
    }

    $conn->begin_transaction();
    try {
        // Auto-log into food_logs (resolved grams, calories already computed on the item).
        $unit = 'g';
        $stmt = $conn->prepare(
            "INSERT INTO food_logs (user_id, food_id, quantity_g, calories_consumed, serving_unit, serving_amount)
             VALUES (?, ?, ?, ?, ?, NULL)"
        );
        $stmt->bind_param('iidds', $patient_id, $item['food_id'], $item['quantity_g'], $item['calories'], $unit);
        if (!$stmt->execute()) { throw new RuntimeException($stmt->error); }
        $log_id = (int) $stmt->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO meal_completions (patient_id, item_id, completed_on, log_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iisi', $patient_id, $item_id, $today, $log_id);
        if (!$stmt->execute()) { throw new RuntimeException($stmt->error); }
        $stmt->close();

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('log_plan_item take failed: ' . $e->getMessage());
        json_response(['success' => false, 'message' => 'Could not log this meal. Please try again.'], 500);
    }

    json_response(['success' => true, 'taken' => true, 'message' => 'Logged to your meal log.']);
}

// action === 'untake'
if (!$existing) {
    json_response(['success' => true, 'taken' => false, 'message' => 'Not logged.']);
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("DELETE FROM meal_completions WHERE completion_id = ?");
    $stmt->bind_param('i', $existing['completion_id']);
    if (!$stmt->execute()) { throw new RuntimeException($stmt->error); }
    $stmt->close();

    // Remove the auto-created food log (ownership re-checked in WHERE).
    if (!empty($existing['log_id'])) {
        $stmt = $conn->prepare("DELETE FROM food_logs WHERE log_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $existing['log_id'], $patient_id);
        if (!$stmt->execute()) { throw new RuntimeException($stmt->error); }
        $stmt->close();
    }

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('log_plan_item untake failed: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Could not update this meal. Please try again.'], 500);
}

json_response(['success' => true, 'taken' => false, 'message' => 'Removed from your meal log.']);

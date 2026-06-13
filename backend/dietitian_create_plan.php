<?php
// backend/dietitian_create_plan.php
// Creates or updates a database-driven diet plan: a set of (meal, food, amount)
// items plus notes. The total calories across the three meals must be <= the
// patient's required daily calories — this is re-validated server-side so the
// client can never bypass it. All writes happen in a single transaction.
// SECURITY: the patient must be assigned to this dietitian.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$dietitian_id = require_role('dietitian');
require_post();
require_csrf();

$patient_id = intval($_POST['patient_id'] ?? 0);
$notes      = trim($_POST['notes'] ?? '');
$itemsJson  = $_POST['items'] ?? '[]';

if ($patient_id <= 0) {
    json_response(['success' => false, 'message' => 'Please select a patient.']);
}

$rawItems = json_decode($itemsJson, true);
if (!is_array($rawItems)) {
    json_response(['success' => false, 'message' => 'Invalid plan data.']);
}
if (count($rawItems) === 0) {
    json_response(['success' => false, 'message' => 'Add at least one food to the plan.']);
}
if (count($rawItems) > 60) {
    json_response(['success' => false, 'message' => 'That plan has too many items.']);
}

// Assignment check + patient metrics for the calorie cap.
$stmt = $conn->prepare("
    SELECT age, gender, height_cm, weight_kg, activity_level
    FROM users
    WHERE user_id = ? AND assigned_dietitian_id = ? AND role = 'patient'
");
$stmt->bind_param('ii', $patient_id, $dietitian_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    json_response(['success' => false, 'message' => 'This patient is not assigned to you. Ask them to send you a request first.'], 403);
}

$daily_need = daily_calorie_need(
    $patient['height_cm'], $patient['weight_kg'], $patient['age'], $patient['gender'], $patient['activity_level']
);

// Validate each item and recompute its calories from the food's verified value.
$validMeals = ['breakfast', 'lunch', 'dinner'];
$clean = [];
$total = 0.0;

$foodStmt = $conn->prepare("SELECT calories_per_100g FROM foods WHERE food_id = ?");
foreach ($rawItems as $it) {
    $meal = is_array($it) ? ($it['meal'] ?? '') : '';
    $fid  = (int) ($it['food_id'] ?? 0);
    $qty  = (float) ($it['quantity_g'] ?? 0);

    if (!in_array($meal, $validMeals, true) || $fid <= 0 || $qty <= 0 || $qty > 5000) {
        $foodStmt->close();
        json_response(['success' => false, 'message' => 'One of the plan items is invalid.']);
    }

    $foodStmt->bind_param('i', $fid);
    $foodStmt->execute();
    $food = $foodStmt->get_result()->fetch_assoc();
    if (!$food) {
        $foodStmt->close();
        json_response(['success' => false, 'message' => 'A selected food no longer exists.']);
    }

    $cal = round(($food['calories_per_100g'] / 100) * $qty, 1);
    $total += $cal;
    $clean[] = ['meal' => $meal, 'food_id' => $fid, 'quantity_g' => round($qty, 1), 'calories' => $cal];
}
$foodStmt->close();

// The core rule: total must not exceed the patient's required calories.
if ($daily_need > 0 && $total > $daily_need) {
    json_response([
        'success' => false,
        'message' => 'Total plan calories (' . round($total) . ' kcal) exceed the patient\'s requirement (' . $daily_need . ' kcal). Reduce amounts to continue.',
    ]);
}

// Persist plan + items atomically.
$conn->begin_transaction();
try {
    // Upsert the plan row and get its id.
    $stmt = $conn->prepare("SELECT plan_id FROM diet_plans WHERE patient_id = ? AND dietitian_id = ?");
    $stmt->bind_param('ii', $patient_id, $dietitian_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $plan_id = (int) $existing['plan_id'];
        $stmt = $conn->prepare("UPDATE diet_plans SET notes = ? WHERE plan_id = ?");
        $stmt->bind_param('si', $notes, $plan_id);
        if (!$stmt->execute()) { throw new RuntimeException($stmt->error); }
        $stmt->close();
        $action = 'updated';

        $del = $conn->prepare("DELETE FROM diet_plan_items WHERE plan_id = ?");
        $del->bind_param('i', $plan_id);
        if (!$del->execute()) { throw new RuntimeException($del->error); }
        $del->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO diet_plans (patient_id, dietitian_id, notes) VALUES (?, ?, ?)");
        $stmt->bind_param('iis', $patient_id, $dietitian_id, $notes);
        if (!$stmt->execute()) { throw new RuntimeException($stmt->error); }
        $plan_id = (int) $stmt->insert_id;
        $stmt->close();
        $action = 'created';
    }

    $ins = $conn->prepare("INSERT INTO diet_plan_items (plan_id, meal, food_id, quantity_g, calories) VALUES (?, ?, ?, ?, ?)");
    foreach ($clean as $c) {
        $ins->bind_param('isidd', $plan_id, $c['meal'], $c['food_id'], $c['quantity_g'], $c['calories']);
        if (!$ins->execute()) { throw new RuntimeException($ins->error); }
    }
    $ins->close();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('create_plan failed: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Failed to save plan. Please try again.'], 500);
}

log_activity($conn, $dietitian_id, 'dietitian', 'plan_' . $action,
    'Plan ' . $action . ' for patient #' . $patient_id . ' (' . round($total) . ' kcal)');

json_response(['success' => true, 'message' => 'Diet plan ' . $action . ' successfully! Total: ' . round($total) . ' kcal.']);

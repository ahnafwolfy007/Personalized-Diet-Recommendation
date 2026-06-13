<?php
// backend/dietitian_get_plan.php
// Returns the editing context for a dietitian's meal plan for one patient:
// the patient's required daily calories (the cap the plan must stay within),
// the existing plan notes, and the existing structured items.
// SECURITY: the patient must be assigned to this dietitian.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$dietitian_id = require_role('dietitian');

$patient_id = intval($_GET['patient_id'] ?? 0);
if ($patient_id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid patient.']);
}

// Assignment check + pull metrics for the calorie cap.
$stmt = $conn->prepare("
    SELECT name, age, gender, height_cm, weight_kg, activity_level
    FROM users
    WHERE user_id = ? AND assigned_dietitian_id = ? AND role = 'patient'
");
$stmt->bind_param('ii', $patient_id, $dietitian_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    json_response(['success' => false, 'message' => 'This patient is not assigned to you.'], 403);
}

$daily_need = daily_calorie_need(
    $patient['height_cm'], $patient['weight_kg'], $patient['age'], $patient['gender'], $patient['activity_level']
);

// Existing plan (if any) for this pair.
$stmt = $conn->prepare("SELECT plan_id, notes FROM diet_plans WHERE patient_id = ? AND dietitian_id = ?");
$stmt->bind_param('ii', $patient_id, $dietitian_id);
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();
$stmt->close();

$items = [];
if ($plan) {
    $stmt = $conn->prepare("
        SELECT i.item_id, i.meal, i.food_id, f.name AS food_name, i.quantity_g, i.calories
        FROM diet_plan_items i
        JOIN foods f ON f.food_id = i.food_id
        WHERE i.plan_id = ?
        ORDER BY FIELD(i.meal, 'breakfast', 'lunch', 'dinner'), i.item_id
    ");
    $stmt->bind_param('i', $plan['plan_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $items[] = [
            'meal'       => $row['meal'],
            'food_id'    => (int) $row['food_id'],
            'food_name'  => $row['food_name'],
            'quantity_g' => (float) $row['quantity_g'],
            'calories'   => (float) $row['calories'],
        ];
    }
    $stmt->close();
}

json_response([
    'success'      => true,
    'patient_name' => $patient['name'],
    'daily_need'   => $daily_need,
    'notes'        => $plan['notes'] ?? '',
    'items'        => $items,
]);

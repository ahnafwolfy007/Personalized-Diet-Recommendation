<?php
// backend/get_diet_plan.php
// Returns the logged-in PATIENT's current diet plan: the dietitian/notes, the
// structured per-meal items (food + amount + calories), and whether each item
// has already been ticked "Taken" today. Legacy free-text fields are returned
// as a fallback for plans created before the database-driven plan feature.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$patient_id = require_role('patient');

// Scope to the patient's current dietitian; fall back to the latest plan for
// legacy data where the assignment may differ.
$stmt = $conn->prepare("SELECT assigned_dietitian_id FROM users WHERE user_id = ?");
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$assigned = (int) ($stmt->get_result()->fetch_assoc()['assigned_dietitian_id'] ?? 0);
$stmt->close();

if ($assigned > 0) {
    $stmt = $conn->prepare("
        SELECT dp.plan_id, dp.breakfast_text, dp.lunch_text, dp.dinner_text, dp.notes,
               dp.created_at, u.name AS dietitian_name
        FROM diet_plans dp
        JOIN users u ON dp.dietitian_id = u.user_id
        WHERE dp.patient_id = ? AND dp.dietitian_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $patient_id, $assigned);
} else {
    $stmt = $conn->prepare("
        SELECT dp.plan_id, dp.breakfast_text, dp.lunch_text, dp.dinner_text, dp.notes,
               dp.created_at, u.name AS dietitian_name
        FROM diet_plans dp
        JOIN users u ON dp.dietitian_id = u.user_id
        WHERE dp.patient_id = ?
        ORDER BY dp.created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param('i', $patient_id);
}
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$plan) {
    json_response(['success' => false, 'message' => 'No diet plan found.']);
}

// Structured items + today's completion state.
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT i.item_id, i.meal, i.food_id, f.name AS food_name, i.quantity_g, i.calories,
           (mc.completion_id IS NOT NULL) AS taken_today
    FROM diet_plan_items i
    JOIN foods f ON f.food_id = i.food_id
    LEFT JOIN meal_completions mc
           ON mc.item_id = i.item_id AND mc.completed_on = ?
    WHERE i.plan_id = ?
    ORDER BY FIELD(i.meal, 'breakfast', 'lunch', 'dinner'), i.item_id
");
$stmt->bind_param('si', $today, $plan['plan_id']);
$stmt->execute();
$res = $stmt->get_result();
$items = [];
while ($row = $res->fetch_assoc()) {
    $row['item_id']     = (int) $row['item_id'];
    $row['food_id']     = (int) $row['food_id'];
    $row['quantity_g']  = (float) $row['quantity_g'];
    $row['calories']    = (float) $row['calories'];
    $row['taken_today'] = (int) $row['taken_today'];
    $items[] = $row;
}
$stmt->close();

json_response(['success' => true, 'plan' => $plan, 'items' => $items]);

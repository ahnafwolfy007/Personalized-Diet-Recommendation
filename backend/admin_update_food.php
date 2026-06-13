<?php
// backend/admin_update_food.php
// Admin edits a food's nutritional info and/or verifies it. If the calories per
// 100g change, every existing food_logs entry (and diet-plan item) for that food
// is recalculated so historical calorie totals stay consistent with the
// corrected value. All of this runs in a single transaction.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$admin_id = require_role('admin');
require_post();
require_csrf();

$food_id     = intval($_POST['food_id'] ?? 0);
$name        = trim($_POST['name'] ?? '');
$category    = trim($_POST['category'] ?? 'General');
$calories    = floatval($_POST['calories_per_100g'] ?? 0);
$is_verified = intval($_POST['is_verified'] ?? 0) === 1 ? 1 : 0;

if ($food_id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid food.']);
}
if ($name === '' || mb_strlen($name) > 150) {
    json_response(['success' => false, 'message' => 'Enter a valid food name.']);
}
if ($calories <= 0 || $calories > 1000) {
    json_response(['success' => false, 'message' => 'Calories per 100g must be between 1 and 1000.']);
}
if (!in_array($category, food_categories(), true)) {
    $category = 'General';
}

// Current value so we can tell if calories changed.
$stmt = $conn->prepare("SELECT calories_per_100g FROM foods WHERE food_id = ?");
$stmt->bind_param('i', $food_id);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current) {
    json_response(['success' => false, 'message' => 'Food not found.'], 404);
}

$old_cal = (float) $current['calories_per_100g'];
$cal_changed = abs($old_cal - $calories) > 0.0001;

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("UPDATE foods SET name = ?, category = ?, calories_per_100g = ?, is_verified = ? WHERE food_id = ?");
    $stmt->bind_param('ssdii', $name, $category, $calories, $is_verified, $food_id);
    if (!$stmt->execute()) { throw new RuntimeException($stmt->error); }
    $stmt->close();

    $recalculated = 0;
    if ($cal_changed) {
        // Recompute every logged entry of this food from its stored grams.
        $stmt = $conn->prepare("UPDATE food_logs SET calories_consumed = ROUND(quantity_g / 100 * ?, 1) WHERE food_id = ?");
        $stmt->bind_param('di', $calories, $food_id);
        if (!$stmt->execute()) { throw new RuntimeException($stmt->error); }
        $recalculated = $stmt->affected_rows;
        $stmt->close();

        // Keep diet-plan items consistent too.
        $stmt = $conn->prepare("UPDATE diet_plan_items SET calories = ROUND(quantity_g / 100 * ?, 1) WHERE food_id = ?");
        $stmt->bind_param('di', $calories, $food_id);
        if (!$stmt->execute()) { throw new RuntimeException($stmt->error); }
        $stmt->close();
    }

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('admin_update_food failed: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Could not update the food. Please try again.'], 500);
}

$detail = ($is_verified ? 'Verified' : 'Updated') . ' food "' . mb_substr($name, 0, 80) . '"';
if ($cal_changed) {
    $detail .= ' (kcal/100g ' . $old_cal . '→' . $calories . ', recalculated ' . $recalculated . ' log entries)';
}
log_activity($conn, $admin_id, 'admin', 'food_update', $detail);

$message = $is_verified ? 'Food verified.' : 'Food updated.';
if ($cal_changed) {
    $message .= ' Recalculated ' . $recalculated . ' existing log ' . ($recalculated === 1 ? 'entry' : 'entries') . '.';
}
json_response(['success' => true, 'message' => $message]);

<?php
// backend/dietitian_create_plan.php
// Creates or updates a diet plan for a patient.
// SECURITY: the patient must be assigned to this dietitian first.

require_once __DIR__ . '/auth.php';

$dietitian_id = require_role('dietitian');
require_post();
require_csrf();

$patient_id     = intval($_POST['patient_id'] ?? 0);
$breakfast_text = trim($_POST['breakfast'] ?? '');
$lunch_text     = trim($_POST['lunch'] ?? '');
$dinner_text    = trim($_POST['dinner'] ?? '');
$notes          = trim($_POST['notes'] ?? '');

if ($patient_id <= 0) {
    json_response(['success' => false, 'message' => 'Please select a patient.']);
}

// SECURITY CHECK: this patient must be assigned to this dietitian.
$check = $conn->prepare("
    SELECT user_id FROM users
    WHERE user_id = ? AND assigned_dietitian_id = ? AND role = 'patient'
");
$check->bind_param('ii', $patient_id, $dietitian_id);
$check->execute();
$assigned = $check->get_result()->fetch_assoc();
$check->close();

if (!$assigned) {
    json_response(['success' => false, 'message' => 'This patient is not assigned to you. Ask them to send you a request first.'], 403);
}

// Does a plan already exist for this patient from this dietitian?
$stmt = $conn->prepare("SELECT plan_id FROM diet_plans WHERE patient_id = ? AND dietitian_id = ?");
$stmt->bind_param('ii', $patient_id, $dietitian_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $stmt = $conn->prepare("UPDATE diet_plans SET breakfast_text=?, lunch_text=?, dinner_text=?, notes=? WHERE plan_id=?");
    $stmt->bind_param('ssssi', $breakfast_text, $lunch_text, $dinner_text, $notes, $existing['plan_id']);
    $action = 'updated';
} else {
    $stmt = $conn->prepare("INSERT INTO diet_plans (patient_id, dietitian_id, breakfast_text, lunch_text, dinner_text, notes) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('iissss', $patient_id, $dietitian_id, $breakfast_text, $lunch_text, $dinner_text, $notes);
    $action = 'created';
}

$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    json_response(['success' => true, 'message' => 'Diet plan ' . $action . ' successfully!']);
} else {
    json_response(['success' => false, 'message' => 'Failed to save plan. Please try again.'], 500);
}

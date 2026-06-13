<?php
// backend/get_diet_plan.php
// Returns a diet plan.
// - If called by a PATIENT (no ?patient_id): returns their own plan
// - If called by a DIETITIAN with ?patient_id=X: returns that patient's plan
//   (only if that patient is assigned to this dietitian)

require_once __DIR__ . '/auth.php';

$user_id = require_login();
$role = $_SESSION['user_role'] ?? '';

// Decide which patient_id to use
if ($role === 'dietitian' && isset($_GET['patient_id'])) {
    $patient_id   = intval($_GET['patient_id']);
    $dietitian_id = $user_id;

    // Security: make sure this patient is assigned to this dietitian
    $check = $conn->prepare("
        SELECT user_id FROM users
        WHERE user_id = ? AND assigned_dietitian_id = ? AND role = 'patient'
    ");
    $check->bind_param('ii', $patient_id, $dietitian_id);
    $check->execute();
    $ok = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$ok) {
        json_response(['success' => false, 'message' => 'Access denied.'], 403);
    }
} else {
    // Patient is looking up their own plan
    $patient_id = $user_id;
}

// Fetch the latest diet plan for this patient
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
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($plan) {
    json_response(['success' => true, 'plan' => $plan]);
} else {
    json_response(['success' => false, 'message' => 'No diet plan found.']);
}

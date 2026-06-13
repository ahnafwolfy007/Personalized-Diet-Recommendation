<?php
// backend/get_profile.php
// Returns the logged-in user's profile data, including their assigned dietitian

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user_id = require_login();

// Get the user's profile, including their assigned dietitian's name
$stmt = $conn->prepare("
    SELECT u.name, u.email, u.age, u.gender, u.height_cm, u.weight_kg, u.activity_level,
           u.assigned_dietitian_id,
           d.name AS dietitian_name
    FROM users u
    LEFT JOIN users d ON d.user_id = u.assigned_dietitian_id
    WHERE u.user_id = ?
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    json_response(['success' => false, 'message' => 'User not found.'], 404);
}

[$bmi, $bmi_label] = calculate_bmi($user['height_cm'], $user['weight_kg']);
$user['bmi']       = $bmi ?? '';
$user['bmi_label'] = $bmi === null ? '' : $bmi_label;

json_response(['success' => true, 'profile' => $user]);

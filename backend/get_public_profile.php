<?php
// backend/get_public_profile.php
// Returns a public-facing profile for any user, so patients can view dietitians
// before requesting, dietitians can view patients before accepting, and admins
// can view anyone. Email is only exposed to the admin or to the user themselves.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$viewer_id   = require_login();
$viewer_role = $_SESSION['user_role'] ?? '';

$target_id = intval($_GET['id'] ?? 0);
if ($target_id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid profile.']);
}

$stmt = $conn->prepare("
    SELECT user_id, name, email, role, status, created_at,
           age, gender, height_cm, weight_kg, activity_level,
           works_at, experience_years, specialization, bio
    FROM users
    WHERE user_id = ?
");
$stmt->bind_param('i', $target_id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$u || $u['role'] === 'admin') {
    // Admin profiles are not public.
    json_response(['success' => false, 'message' => 'Profile not found.'], 404);
}

$is_self = ($target_id === $viewer_id);

// Authorization: dietitian profiles are viewable by any logged-in user (patients
// choose a dietitian, etc.). Patient profiles — which carry health data — are only
// viewable by the admin, a dietitian (deciding on a request), or the patient
// themselves. This prevents one patient from enumerating another's health info.
if ($u['role'] === 'patient' && !$is_self
    && $viewer_role !== 'admin' && $viewer_role !== 'dietitian') {
    json_response(['success' => false, 'message' => 'You are not allowed to view this profile.'], 403);
}

$can_see_email = $is_self || $viewer_role === 'admin';

$profile = [
    'user_id'    => (int) $u['user_id'],
    'name'       => $u['name'],
    'role'       => $u['role'],
    'created_at' => date('M Y', strtotime($u['created_at'])),
    'is_self'    => $is_self,
];
if ($can_see_email) {
    $profile['email'] = $u['email'];
}

if ($u['role'] === 'patient') {
    [$bmi, $bmi_label] = calculate_bmi($u['height_cm'], $u['weight_kg']);
    $profile['age']            = $u['age'] !== null ? (int) $u['age'] : null;
    $profile['gender']         = $u['gender'];
    $profile['height_cm']      = $u['height_cm'] !== null ? (float) $u['height_cm'] : null;
    $profile['weight_kg']      = $u['weight_kg'] !== null ? (float) $u['weight_kg'] : null;
    $profile['activity_level'] = $u['activity_level'];
    $profile['bmi']            = $bmi;
    $profile['bmi_label']      = $bmi === null ? '' : $bmi_label;
} else { // dietitian
    $profile['works_at']         = $u['works_at'];
    $profile['experience_years'] = $u['experience_years'] !== null ? (int) $u['experience_years'] : null;
    $profile['specialization']   = $u['specialization'];
    $profile['bio']              = $u['bio'];

    // A small public stat: how many patients this dietitian currently has.
    $c = $conn->prepare("SELECT COUNT(*) AS cnt FROM users WHERE assigned_dietitian_id = ? AND role = 'patient'");
    $c->bind_param('i', $target_id);
    $c->execute();
    $profile['patient_count'] = (int) $c->get_result()->fetch_assoc()['cnt'];
    $c->close();
}

json_response(['success' => true, 'profile' => $profile]);

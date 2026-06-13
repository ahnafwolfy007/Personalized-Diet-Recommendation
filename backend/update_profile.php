<?php
// backend/update_profile.php
// Updates the logged-in user's profile. Patients update health metrics;
// dietitians update professional details. Both write to the users table.

require_once __DIR__ . '/auth.php';

$user_id = require_login();
$role    = $_SESSION['user_role'] ?? '';
require_post();
require_csrf();

$name = trim($_POST['name'] ?? '');
if ($name === '') {
    json_response(['success' => false, 'message' => 'Name cannot be empty.']);
}
$name = mb_substr($name, 0, 100);

if ($role === 'dietitian') {
    $works_at         = trim($_POST['works_at'] ?? '');
    $experience_years = intval($_POST['experience_years'] ?? 0);
    $specialization   = trim($_POST['specialization'] ?? '');
    $bio              = trim($_POST['bio'] ?? '');

    $works_at         = $works_at !== '' ? mb_substr($works_at, 0, 150) : null;
    $experience_years = $experience_years > 0 ? min($experience_years, 80) : null;
    $specialization   = $specialization !== '' ? mb_substr($specialization, 0, 150) : null;
    $bio              = $bio !== '' ? mb_substr($bio, 0, 2000) : null;

    $stmt = $conn->prepare("UPDATE users SET name=?, works_at=?, experience_years=?, specialization=?, bio=? WHERE user_id=?");
    $stmt->bind_param('ssissi', $name, $works_at, $experience_years, $specialization, $bio, $user_id);
} else {
    $age            = intval($_POST['age'] ?? 0);
    $gender         = trim($_POST['gender'] ?? '');
    $height         = floatval($_POST['height'] ?? 0);
    $weight         = floatval($_POST['weight'] ?? 0);
    $activity_level = trim($_POST['activity_level'] ?? '');

    // Normalize optional fields so blanks are stored as NULL rather than 0/''.
    $age            = $age > 0 ? $age : null;
    $gender         = in_array($gender, ['Male', 'Female', 'Other'], true) ? $gender : null;
    $height         = $height > 0 ? $height : null;
    $weight         = $weight > 0 ? $weight : null;
    $activity_level = $activity_level !== '' ? $activity_level : null;

    $stmt = $conn->prepare("UPDATE users SET name=?, age=?, gender=?, height_cm=?, weight_kg=?, activity_level=? WHERE user_id=?");
    $stmt->bind_param('sisddsi', $name, $age, $gender, $height, $weight, $activity_level, $user_id);
}

$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    $_SESSION['user_name'] = $name;
    json_response(['success' => true, 'message' => 'Profile updated successfully.']);
}
error_log('update_profile failed: ' . $conn->error);
json_response(['success' => false, 'message' => 'Update failed. Please try again.'], 500);

<?php
// backend/update_profile.php
// Updates the logged-in user's profile data

require_once __DIR__ . '/auth.php';

$user_id = require_login();
require_post();
require_csrf();

$name           = trim($_POST['name'] ?? '');
$age            = intval($_POST['age'] ?? 0);
$gender         = trim($_POST['gender'] ?? '');
$height         = floatval($_POST['height'] ?? 0);
$weight         = floatval($_POST['weight'] ?? 0);
$activity_level = trim($_POST['activity_level'] ?? '');

if ($name === '') {
    json_response(['success' => false, 'message' => 'Name cannot be empty.']);
}

// Normalize optional fields so blanks are stored as NULL rather than 0/''.
$age            = $age > 0 ? $age : null;
$gender         = in_array($gender, ['Male', 'Female', 'Other'], true) ? $gender : null;
$height         = $height > 0 ? $height : null;
$weight         = $weight > 0 ? $weight : null;
$activity_level = $activity_level !== '' ? $activity_level : null;

// Types: name=s, age=i, gender=s, height=d, weight=d, activity=s, user_id=i
$stmt = $conn->prepare("UPDATE users SET name=?, age=?, gender=?, height_cm=?, weight_kg=?, activity_level=? WHERE user_id=?");
$stmt->bind_param('sisddsi', $name, $age, $gender, $height, $weight, $activity_level, $user_id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    $_SESSION['user_name'] = $name;
    json_response(['success' => true, 'message' => 'Profile updated successfully.']);
} else {
    json_response(['success' => false, 'message' => 'Update failed. Please try again.'], 500);
}

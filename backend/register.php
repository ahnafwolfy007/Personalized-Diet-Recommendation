<?php
// backend/register.php
// Handles registration from frontend/register.php. A user picks a role
// (patient or dietitian) and fills a role-specific form. Both roles are saved
// to the same users table; role-irrelevant columns are stored as NULL.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

require_post();

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$role     = trim($_POST['role'] ?? 'patient');

// Only patient or dietitian may self-register (never admin).
if (!in_array($role, ['patient', 'dietitian'], true)) {
    $role = 'patient';
}

// Shared validation.
if ($name === '' || $email === '' || $password === '') {
    json_response(['success' => false, 'message' => 'Name, email and password are required.']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email address.']);
}
if (strlen($password) < 6) {
    json_response(['success' => false, 'message' => 'Password must be at least 6 characters.']);
}

// Role-specific fields (everything optional, normalized to NULL when blank).
$age = $gender = $height = $weight = $activity_level = null;
$works_at = $experience_years = $specialization = $bio = null;

if ($role === 'patient') {
    $age            = intval($_POST['age'] ?? 0);
    $gender         = trim($_POST['gender'] ?? '');
    $height         = floatval($_POST['height'] ?? 0);
    $weight         = floatval($_POST['weight'] ?? 0);
    $activity_level = trim($_POST['activity_level'] ?? '');

    $age            = $age > 0 ? $age : null;
    $gender         = in_array($gender, ['Male', 'Female', 'Other'], true) ? $gender : null;
    $height         = $height > 0 ? $height : null;
    $weight         = $weight > 0 ? $weight : null;
    $activity_level = $activity_level !== '' ? $activity_level : null;
} else { // dietitian
    $works_at         = trim($_POST['works_at'] ?? '');
    $experience_years = intval($_POST['experience_years'] ?? 0);
    $specialization   = trim($_POST['specialization'] ?? '');
    $bio              = trim($_POST['bio'] ?? '');

    $works_at         = $works_at !== '' ? mb_substr($works_at, 0, 150) : null;
    $experience_years = $experience_years > 0 ? min($experience_years, 80) : null;
    $specialization   = $specialization !== '' ? mb_substr($specialization, 0, 150) : null;
    $bio              = $bio !== '' ? mb_substr($bio, 0, 2000) : null;
}

// Email must be unique.
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    json_response(['success' => false, 'message' => 'This email is already registered.']);
}
$stmt->close();

$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare(
    "INSERT INTO users
        (name, email, password, role, age, gender, height_cm, weight_kg, activity_level,
         works_at, experience_years, specialization, bio)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
// types: name s, email s, pass s, role s, age i, gender s, height d, weight d,
//        activity s, works_at s, experience i, specialization s, bio s
$stmt->bind_param(
    'ssssisddssiss',
    $name, $email, $hashed, $role, $age, $gender, $height, $weight, $activity_level,
    $works_at, $experience_years, $specialization, $bio
);

if (!$stmt->execute()) {
    $stmt->close();
    error_log('register failed: ' . $conn->error);
    json_response(['success' => false, 'message' => 'Registration failed. Please try again.'], 500);
}

$user_id = $stmt->insert_id;
$stmt->close();

log_activity($conn, (int) $user_id, $role, 'register', 'Registered as ' . $role);

// Auto-login after registration (fresh session id to prevent fixation).
session_regenerate_id(true);
$_SESSION['user_id']   = (int) $user_id;
$_SESSION['user_name'] = $name;
$_SESSION['user_role'] = $role;

$redirect = ($role === 'dietitian')
    ? '../frontend/dietitian-dashboard.php'
    : '../frontend/user-dashboard.php';

json_response(['success' => true, 'redirect' => $redirect]);

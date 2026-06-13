<?php
// backend/register.php
// Handles registration form from frontend/register.php

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$name           = trim($_POST['name'] ?? '');
$email          = trim($_POST['email'] ?? '');
$password       = (string) ($_POST['password'] ?? '');
$age            = intval($_POST['age'] ?? 0);
$gender         = trim($_POST['gender'] ?? '');
$height         = floatval($_POST['height'] ?? 0);
$weight         = floatval($_POST['weight'] ?? 0);
$activity_level = trim($_POST['activity_level'] ?? '');
$role           = trim($_POST['role'] ?? 'patient');

// Validation
if ($name === '' || $email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Name, email and password are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}

// Only allow patient or dietitian registration (not admin)
if (!in_array($role, ['patient', 'dietitian'], true)) {
    $role = 'patient';
}
// Normalize optional fields to NULL when not provided.
$gender         = in_array($gender, ['Male', 'Female', 'Other'], true) ? $gender : null;
$age            = $age > 0 ? $age : null;
$height         = $height > 0 ? $height : null;
$weight         = $weight > 0 ? $weight : null;
$activity_level = $activity_level !== '' ? $activity_level : null;

// Check if email already exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This email is already registered.']);
    $stmt->close();
    exit;
}
$stmt->close();

// Hash password
$hashed = password_hash($password, PASSWORD_BCRYPT);

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (name, email, password, role, age, gender, height_cm, weight_kg, activity_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssssisdds', $name, $email, $hashed, $role, $age, $gender, $height, $weight, $activity_level);

if ($stmt->execute()) {
    $user_id = $stmt->insert_id;
    $stmt->close();

    // Auto-login after registration (fresh session id to prevent fixation).
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int) $user_id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = $role;

    $redirect = ($role === 'dietitian')
        ? '../frontend/dietitian-dashboard.php'
        : '../frontend/user-dashboard.php';

    echo json_encode(['success' => true, 'redirect' => $redirect]);
} else {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}

<?php
// backend/login.php
// Handles login form submission from frontend/login.php

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$role     = trim($_POST['role'] ?? '');

if ($email === '' || $password === '' || $role === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}

// Find user by email and role
$stmt = $conn->prepare("SELECT user_id, name, email, password, role, status FROM users WHERE email = ? AND role = ?");
$stmt->bind_param('ss', $email, $role);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Single generic message for bad email/role/password to avoid account enumeration.
if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid email, password, or role.']);
    exit;
}

if ($user['status'] === 'inactive') {
    echo json_encode(['success' => false, 'message' => 'Your account is inactive. Please contact an administrator.']);
    exit;
}

// Prevent session fixation: issue a fresh session id at privilege change.
session_regenerate_id(true);
$_SESSION['user_id']   = (int) $user['user_id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_role'] = $user['role'];

// Redirect based on role
$redirect = '../frontend/user-dashboard.php';
if ($user['role'] === 'dietitian') {
    $redirect = '../frontend/dietitian-dashboard.php';
} elseif ($user['role'] === 'admin') {
    $redirect = '../frontend/admin-dashboard.php';
}

echo json_encode(['success' => true, 'redirect' => $redirect, 'role' => $user['role'], 'name' => $user['name']]);

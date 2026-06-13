<?php
// backend/login.php
// Handles login form submission from frontend/login.php.
// Email + password only — the role is derived from the account, not supplied
// by the client (a user cannot pick which role to log in as).

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

require_post();

$email    = trim($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    json_response(['success' => false, 'message' => 'Please enter your email and password.']);
}

// Find user by email only; the role comes from the stored account.
$stmt = $conn->prepare("SELECT user_id, name, email, password, role, status FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Single generic message for a bad email or password to avoid account enumeration.
if (!$user || !password_verify($password, $user['password'])) {
    json_response(['success' => false, 'message' => 'Invalid email or password.']);
}

if ($user['status'] === 'inactive') {
    json_response(['success' => false, 'message' => 'Your account is inactive. Please contact an administrator.']);
}

// Prevent session fixation: issue a fresh session id at privilege change.
session_regenerate_id(true);
$_SESSION['user_id']   = (int) $user['user_id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_role'] = $user['role'];

log_activity($conn, (int) $user['user_id'], $user['role'], 'login', null);

// Redirect based on role.
$redirect = '../frontend/user-dashboard.php';
if ($user['role'] === 'dietitian') {
    $redirect = '../frontend/dietitian-dashboard.php';
} elseif ($user['role'] === 'admin') {
    $redirect = '../frontend/admin-dashboard.php';
}

json_response(['success' => true, 'redirect' => $redirect, 'role' => $user['role'], 'name' => $user['name']]);

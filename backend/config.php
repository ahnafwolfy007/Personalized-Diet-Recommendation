<?php
// backend/config.php
// Database connection + session bootstrap, shared by all backend endpoints.
// Change DB_USER / DB_PASS to match your local MySQL setup.

require_once __DIR__ . '/session.php';

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change if needed
define('DB_PASS', '');           // Change if needed
define('DB_NAME', 'dietsync');

// Keep procedural error semantics (return false / populate ->error) rather than
// throwing, so the per-endpoint error handling below stays valid across
// PHP 8.0 (reporting off by default) and 8.1+ (reporting on by default).
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_errno) {
    // Log the real reason server-side; never expose connection internals to clients.
    error_log('DietSync DB connection failed: ' . $conn->connect_error);
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'A server error occurred. Please try again later.']);
    exit;
}

$conn->set_charset('utf8mb4');

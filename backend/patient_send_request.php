<?php
// backend/patient_send_request.php
// Patient sends a request to a specific dietitian

require_once __DIR__ . '/auth.php';

$patient_id = require_role('patient');
require_post();
require_csrf();

$dietitian_id = intval($_POST['dietitian_id'] ?? 0);

if ($dietitian_id <= 0) {
    json_response(['success' => false, 'message' => 'Please select a valid dietitian.']);
}

// Validate the target is actually an active dietitian (not just any user id).
$check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'dietitian' AND status = 'active'");
$check->bind_param('i', $dietitian_id);
$check->execute();
$validDietitian = $check->get_result()->fetch_assoc();
$check->close();

if (!$validDietitian) {
    json_response(['success' => false, 'message' => 'That dietitian is not available.']);
}

// Block duplicate active requests (pending or already accepted).
$check = $conn->prepare("
    SELECT status FROM dietitian_requests
    WHERE patient_id = ? AND status IN ('pending', 'accepted')
    LIMIT 1
");
$check->bind_param('i', $patient_id);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();

if ($existing) {
    $message = $existing['status'] === 'accepted'
        ? 'You already have an assigned dietitian.'
        : 'You already have a pending request. Please wait for a response.';
    json_response(['success' => false, 'message' => $message]);
}

$stmt = $conn->prepare("INSERT INTO dietitian_requests (patient_id, dietitian_id) VALUES (?, ?)");
$stmt->bind_param('ii', $patient_id, $dietitian_id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    json_response(['success' => true, 'message' => 'Request sent! Please wait for the dietitian to accept.']);
} else {
    json_response(['success' => false, 'message' => 'Failed to send request. Please try again.'], 500);
}

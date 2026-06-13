<?php
// backend/patient_send_feedback.php
// Patient sends a feedback message to their assigned dietitian.

require_once __DIR__ . '/auth.php';

$patient_id = require_role('patient');
require_post();
require_csrf();

$message = trim($_POST['message'] ?? '');

if ($message === '') {
    json_response(['success' => false, 'message' => 'Message cannot be empty.']);
}
if (mb_strlen($message) > 2000) {
    json_response(['success' => false, 'message' => 'Message is too long (2000 characters max).']);
}

// The patient must have an assigned dietitian to send feedback to.
$stmt = $conn->prepare("SELECT assigned_dietitian_id FROM users WHERE user_id = ?");
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$dietitian_id = $row['assigned_dietitian_id'] ?? null;
if (!$dietitian_id) {
    json_response(['success' => false, 'message' => 'You need an assigned dietitian before sending feedback.']);
}

$stmt = $conn->prepare("INSERT INTO feedbacks (patient_id, dietitian_id, message) VALUES (?, ?, ?)");
$stmt->bind_param('iis', $patient_id, $dietitian_id, $message);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    json_response(['success' => true, 'message' => 'Feedback sent to your dietitian.']);
} else {
    json_response(['success' => false, 'message' => 'Failed to send feedback. Please try again.'], 500);
}

<?php
// backend/dietitian_send_feedback.php
// Saves a dietitian's response to a patient's feedback

require_once __DIR__ . '/auth.php';

$dietitian_id = require_role('dietitian');
require_post();
require_csrf();

$feedback_id = intval($_POST['feedback_id'] ?? 0);
$response    = trim($_POST['response'] ?? '');

if ($feedback_id <= 0 || $response === '') {
    json_response(['success' => false, 'message' => 'Response cannot be empty.']);
}

// Only allow responding to feedback addressed to this dietitian.
$stmt = $conn->prepare("UPDATE feedbacks SET response=?, status='responded' WHERE feedback_id=? AND dietitian_id=?");
$stmt->bind_param('sii', $response, $feedback_id, $dietitian_id);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected > 0) {
    json_response(['success' => true, 'message' => 'Response sent.']);
} else {
    json_response(['success' => false, 'message' => 'Feedback not found or already answered.'], 404);
}

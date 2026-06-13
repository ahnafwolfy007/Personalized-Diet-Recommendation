<?php
// backend/patient_get_feedback.php
// Returns the logged-in patient's own feedback messages and any responses,
// plus whether they currently have an assigned dietitian (so the page can
// show the right empty/disabled state).

require_once __DIR__ . '/auth.php';

$patient_id = require_role('patient');

$stmt = $conn->prepare("SELECT assigned_dietitian_id FROM users WHERE user_id = ?");
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$has_dietitian = !empty($row['assigned_dietitian_id']);

$stmt = $conn->prepare("
    SELECT fb.feedback_id, fb.message, fb.response, fb.status, fb.created_at,
           u.name AS dietitian_name
    FROM feedbacks fb
    JOIN users u ON fb.dietitian_id = u.user_id
    WHERE fb.patient_id = ?
    ORDER BY fb.created_at DESC
");
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$feedbacks = [];
while ($fb = $result->fetch_assoc()) {
    $fb['created_at'] = date('F j, Y', strtotime($fb['created_at']));
    $feedbacks[] = $fb;
}
$stmt->close();

json_response(['success' => true, 'has_dietitian' => $has_dietitian, 'feedbacks' => $feedbacks]);

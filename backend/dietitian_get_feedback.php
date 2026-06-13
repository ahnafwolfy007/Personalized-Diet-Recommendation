<?php
// backend/dietitian_get_feedback.php
// Returns all feedback messages addressed to the logged-in dietitian

require_once __DIR__ . '/auth.php';

$dietitian_id = require_role('dietitian');

$stmt = $conn->prepare("
    SELECT fb.feedback_id, fb.message, fb.response, fb.status, fb.created_at,
           u.name AS patient_name
    FROM feedbacks fb
    JOIN users u ON fb.patient_id = u.user_id
    WHERE fb.dietitian_id = ?
    ORDER BY fb.created_at DESC
");
$stmt->bind_param('i', $dietitian_id);
$stmt->execute();
$result = $stmt->get_result();

$feedbacks = [];
while ($row = $result->fetch_assoc()) {
    $row['created_at'] = date('F j, Y', strtotime($row['created_at']));
    $feedbacks[] = $row;
}
$stmt->close();

json_response(['success' => true, 'feedbacks' => $feedbacks]);

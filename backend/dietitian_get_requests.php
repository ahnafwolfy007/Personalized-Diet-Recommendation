<?php
// backend/dietitian_get_requests.php
// Returns all pending patient requests for the logged-in dietitian

require_once __DIR__ . '/auth.php';

$dietitian_id = require_role('dietitian');

$stmt = $conn->prepare("
    SELECT r.request_id, r.status, r.created_at,
           u.user_id AS patient_id, u.name AS patient_name, u.age, u.gender
    FROM dietitian_requests r
    JOIN users u ON u.user_id = r.patient_id
    WHERE r.dietitian_id = ? AND r.status = 'pending'
    ORDER BY r.created_at DESC
");
$stmt->bind_param('i', $dietitian_id);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $row['created_at'] = date('M j, Y g:i A', strtotime($row['created_at']));
    $requests[] = $row;
}
$stmt->close();

json_response(['success' => true, 'requests' => $requests]);

<?php
// backend/patient_check_request.php
// Checks if the logged-in patient already has a pending request

require_once __DIR__ . '/auth.php';

if (empty($_SESSION['user_id'])) {
    json_response(['has_pending' => false]);
}
$patient_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT request_id FROM dietitian_requests
    WHERE patient_id = ? AND status = 'pending'
    LIMIT 1
");
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

json_response(['has_pending' => ($result !== null)]);

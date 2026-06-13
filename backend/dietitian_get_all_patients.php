<?php
// backend/dietitian_get_all_patients.php
// Returns ONLY the patients who are assigned to the logged-in dietitian
// (i.e. patients who sent a request and the dietitian accepted it)

require_once __DIR__ . '/auth.php';

$dietitian_id = require_role('dietitian');

$stmt = $conn->prepare("
    SELECT user_id, name, email
    FROM users
    WHERE role = 'patient'
      AND status = 'active'
      AND assigned_dietitian_id = ?
    ORDER BY name ASC
");
$stmt->bind_param('i', $dietitian_id);
$stmt->execute();
$result = $stmt->get_result();

$patients = [];
while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
}
$stmt->close();

json_response(['success' => true, 'patients' => $patients]);

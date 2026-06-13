<?php
// backend/get_dietitians.php
// Returns a list of all active dietitians so a patient can request one

require_once __DIR__ . '/auth.php';

require_login();

$stmt = $conn->prepare("SELECT user_id, name FROM users WHERE role = 'dietitian' AND status = 'active' ORDER BY name ASC");
$stmt->execute();
$result = $stmt->get_result();

$dietitians = [];
while ($row = $result->fetch_assoc()) {
    $dietitians[] = $row;
}
$stmt->close();

json_response(['success' => true, 'dietitians' => $dietitians]);

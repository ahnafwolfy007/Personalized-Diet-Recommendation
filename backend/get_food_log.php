<?php
// backend/get_food_log.php
// Returns food log entries for the logged-in patient for a given day

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user_id = require_login();

$date = $_GET['date'] ?? date('Y-m-d');
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    $date = date('Y-m-d');
}

[$start, $end] = day_bounds($date);

$stmt = $conn->prepare("
    SELECT fl.log_id, f.name AS food_name, fl.quantity_g, fl.calories_consumed, fl.logged_at
    FROM food_logs fl
    JOIN foods f ON fl.food_id = f.food_id
    WHERE fl.user_id = ? AND fl.logged_at >= ? AND fl.logged_at < ?
    ORDER BY fl.logged_at DESC
");
$stmt->bind_param('iss', $user_id, $start, $end);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $row['time'] = date('h:i A', strtotime($row['logged_at']));
    $logs[] = $row;
}
$stmt->close();

json_response(['success' => true, 'logs' => $logs]);

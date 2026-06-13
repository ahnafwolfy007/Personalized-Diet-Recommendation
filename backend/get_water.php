<?php
// backend/get_water.php
// Returns the logged-in patient's water intake: today's total + entries, the
// daily goal, and a 7-day history for the small bar chart.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user_id = require_role('patient');

const WATER_GOAL_ML = 2000;

[$start, $end] = day_bounds(date('Y-m-d'));

// Today's total + individual entries.
$stmt = $conn->prepare("
    SELECT water_id, amount_ml, logged_at
    FROM water_logs
    WHERE user_id = ? AND logged_at >= ? AND logged_at < ?
    ORDER BY logged_at DESC
");
$stmt->bind_param('iss', $user_id, $start, $end);
$stmt->execute();
$res = $stmt->get_result();
$today_total = 0;
$entries = [];
while ($row = $res->fetch_assoc()) {
    $today_total += (int) $row['amount_ml'];
    $entries[] = [
        'water_id'  => (int) $row['water_id'],
        'amount_ml' => (int) $row['amount_ml'],
        'time'      => date('h:i A', strtotime($row['logged_at'])),
    ];
}
$stmt->close();

// 7-day history (oldest → newest) as daily totals.
$weekStart = date('Y-m-d 00:00:00', strtotime('-6 days'));
$stmt = $conn->prepare("
    SELECT DATE(logged_at) AS d, SUM(amount_ml) AS total
    FROM water_logs
    WHERE user_id = ? AND logged_at >= ?
    GROUP BY DATE(logged_at)
");
$stmt->bind_param('is', $user_id, $weekStart);
$stmt->execute();
$res = $stmt->get_result();
$totalsByDay = [];
while ($row = $res->fetch_assoc()) {
    $totalsByDay[$row['d']] = (int) $row['total'];
}
$stmt->close();

$history = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $history[] = [
        'label'  => date('D', strtotime($day)),
        'total'  => $totalsByDay[$day] ?? 0,
    ];
}

json_response([
    'success'     => true,
    'goal_ml'     => WATER_GOAL_ML,
    'today_total' => $today_total,
    'entries'     => $entries,
    'history'     => $history,
]);

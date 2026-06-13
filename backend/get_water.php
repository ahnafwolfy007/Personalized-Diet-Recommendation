<?php
// backend/get_water.php
// Returns the logged-in patient's water intake (today total + entries, a 7-day
// history) and the daily goal. Water rows live in food_logs (entry_type='water',
// quantity_g = millilitres). The goal is the one the patient's dietitian set on
// their plan, falling back to a sensible default.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user_id = require_role('patient');

const WATER_GOAL_DEFAULT = 2000;

// Goal: dietitian-set value from the patient's current plan, else the default.
$goal = WATER_GOAL_DEFAULT;
$stmt = $conn->prepare("
    SELECT dp.water_goal_ml
    FROM users u
    JOIN diet_plans dp ON dp.patient_id = u.user_id AND dp.dietitian_id = u.assigned_dietitian_id
    WHERE u.user_id = ?
    LIMIT 1
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($row && $row['water_goal_ml'] !== null && (int) $row['water_goal_ml'] > 0) {
    $goal = (int) $row['water_goal_ml'];
}

[$start, $end] = day_bounds(date('Y-m-d'));

// Today's total + individual entries.
$stmt = $conn->prepare("
    SELECT log_id, quantity_g AS amount_ml, logged_at
    FROM food_logs
    WHERE user_id = ? AND entry_type = 'water' AND logged_at >= ? AND logged_at < ?
    ORDER BY logged_at DESC
");
$stmt->bind_param('iss', $user_id, $start, $end);
$stmt->execute();
$res = $stmt->get_result();
$today_total = 0;
$entries = [];
while ($r = $res->fetch_assoc()) {
    $today_total += (int) round($r['amount_ml']);
    $entries[] = [
        'water_id'  => (int) $r['log_id'],
        'amount_ml' => (int) round($r['amount_ml']),
        'time'      => date('h:i A', strtotime($r['logged_at'])),
    ];
}
$stmt->close();

// 7-day history (oldest → newest) as daily totals.
$weekStart = date('Y-m-d 00:00:00', strtotime('-6 days'));
$stmt = $conn->prepare("
    SELECT DATE(logged_at) AS d, SUM(quantity_g) AS total
    FROM food_logs
    WHERE user_id = ? AND entry_type = 'water' AND logged_at >= ?
    GROUP BY DATE(logged_at)
");
$stmt->bind_param('is', $user_id, $weekStart);
$stmt->execute();
$res = $stmt->get_result();
$totalsByDay = [];
while ($r = $res->fetch_assoc()) { $totalsByDay[$r['d']] = (int) round($r['total']); }
$stmt->close();

$history = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $history[] = ['label' => date('D', strtotime($day)), 'total' => $totalsByDay[$day] ?? 0];
}

json_response([
    'success'     => true,
    'goal_ml'     => $goal,
    'today_total' => $today_total,
    'entries'     => $entries,
    'history'     => $history,
]);

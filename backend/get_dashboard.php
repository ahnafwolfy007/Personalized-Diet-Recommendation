<?php
// backend/get_dashboard.php
// Returns dashboard stats for the logged-in patient:
// daily calorie need, today's intake, remaining, BMI, recent food logs

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user_id = require_login();

// Get user profile data
$stmt = $conn->prepare("SELECT name, age, gender, height_cm, weight_kg, activity_level FROM users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    json_response(['success' => false, 'message' => 'User not found.'], 404);
}

[$bmi, $bmi_label] = calculate_bmi($user['height_cm'], $user['weight_kg']);
$daily_need = daily_calorie_need($user['height_cm'], $user['weight_kg'], $user['age'], $user['gender'], $user['activity_level']);

// Today's total calorie intake (sargable range so the (user_id, logged_at) index is used)
[$start, $end] = day_bounds(date('Y-m-d'));
$stmt = $conn->prepare("SELECT SUM(calories_consumed) AS total FROM food_logs WHERE user_id = ? AND logged_at >= ? AND logged_at < ?");
$stmt->bind_param('iss', $user_id, $start, $end);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$today_intake = (int) round($row['total'] ?? 0);
$stmt->close();

$remaining = max(0, $daily_need - $today_intake);

// Get 5 most recent food logs
$stmt = $conn->prepare("
    SELECT f.name AS food_name, fl.quantity_g, fl.calories_consumed, fl.logged_at
    FROM food_logs fl
    JOIN foods f ON fl.food_id = f.food_id
    WHERE fl.user_id = ?
    ORDER BY fl.logged_at DESC
    LIMIT 5
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$logs_result = $stmt->get_result();
$recent_logs = [];
while ($log = $logs_result->fetch_assoc()) {
    $log['logged_at'] = date('h:i A', strtotime($log['logged_at']));
    $recent_logs[] = $log;
}
$stmt->close();

json_response([
    'success'      => true,
    'name'         => $user['name'],
    'daily_need'   => $daily_need,
    'today_intake' => $today_intake,
    'remaining'    => $remaining,
    'bmi'          => $bmi ?? '',
    'bmi_label'    => $bmi === null ? 'N/A' : $bmi_label,
    'recent_logs'  => $recent_logs,
]);

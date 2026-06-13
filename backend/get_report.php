<?php
// backend/get_report.php
// Returns daily calorie report for the logged-in patient

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user_id = require_login();

$date = $_GET['date'] ?? date('Y-m-d');
// Validate the date; fall back to today on anything malformed.
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    $date = date('Y-m-d');
}

// Get user data to compute daily need
$stmt = $conn->prepare("SELECT age, gender, height_cm, weight_kg, activity_level FROM users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$daily_need = $user ? daily_calorie_need($user['height_cm'], $user['weight_kg'], $user['age'], $user['gender'], $user['activity_level']) : 0;
if ($daily_need <= 0) {
    $daily_need = 2000; // sensible fallback when the profile is incomplete
}

[$start, $end] = day_bounds($date);

// Total intake for the given date
$stmt = $conn->prepare("SELECT SUM(calories_consumed) AS total FROM food_logs WHERE user_id = ? AND logged_at >= ? AND logged_at < ?");
$stmt->bind_param('iss', $user_id, $start, $end);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$total_intake = (int) round($row['total'] ?? 0);
$stmt->close();

// All food logs for the given date, grouped into meals by time of day
$stmt = $conn->prepare("
    SELECT f.name AS food_name, fl.quantity_g, fl.calories_consumed, fl.logged_at
    FROM food_logs fl
    JOIN foods f ON fl.food_id = f.food_id
    WHERE fl.user_id = ? AND fl.logged_at >= ? AND fl.logged_at < ?
    ORDER BY fl.logged_at ASC
");
$stmt->bind_param('iss', $user_id, $start, $end);
$stmt->execute();
$result = $stmt->get_result();
$meals = [];
while ($row = $result->fetch_assoc()) {
    $hour = (int) date('G', strtotime($row['logged_at']));
    if ($hour >= 5 && $hour < 11)       $meal = 'Breakfast';
    elseif ($hour >= 11 && $hour < 15)  $meal = 'Lunch';
    elseif ($hour >= 15 && $hour < 18)  $meal = 'Snack';
    else                                $meal = 'Dinner';

    if (!isset($meals[$meal])) {
        $meals[$meal] = ['meal' => $meal, 'foods' => [], 'total_calories' => 0];
    }
    $meals[$meal]['foods'][]        = $row['food_name'] . ' (' . $row['quantity_g'] . 'g)';
    $meals[$meal]['total_calories'] += $row['calories_consumed'];
}
$stmt->close();

$over_under = $total_intake - $daily_need;
$percent    = $daily_need > 0 ? (int) round(($total_intake / $daily_need) * 100) : 0;

json_response([
    'success'      => true,
    'date'         => $date,
    'daily_need'   => $daily_need,
    'total_intake' => $total_intake,
    'over_under'   => $over_under,
    'percent'      => $percent,
    'meals'        => array_values($meals),
]);

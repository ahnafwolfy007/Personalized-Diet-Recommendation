<?php
// backend/dietitian_get_patients.php
// Returns the patients assigned to the logged-in dietitian, with today's intake.
// Today's intake is aggregated in a single query (no per-patient N+1 lookups).

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$dietitian_id = require_role('dietitian');

[$start, $end] = day_bounds(date('Y-m-d'));
[$page, $perPage, $offset] = pagination_args(10);

$total = (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE assigned_dietitian_id = " . (int) $dietitian_id . " AND role = 'patient'")->fetch_assoc()['c'];

// Page the patients FIRST (inner subquery), then join only that page's logs —
// today's intake is still aggregated in one query, with no per-patient N+1.
$stmt = $conn->prepare("
    SELECT u.user_id, u.name, u.age, u.gender, u.height_cm, u.weight_kg, u.activity_level,
           COALESCE(SUM(fl.calories_consumed), 0) AS today_intake
    FROM (
        SELECT user_id, name, age, gender, height_cm, weight_kg, activity_level
        FROM users
        WHERE assigned_dietitian_id = ? AND role = 'patient'
        ORDER BY name ASC
        LIMIT ? OFFSET ?
    ) u
    LEFT JOIN food_logs fl
           ON fl.user_id = u.user_id
          AND fl.logged_at >= ? AND fl.logged_at < ?
    GROUP BY u.user_id, u.name, u.age, u.gender, u.height_cm, u.weight_kg, u.activity_level
    ORDER BY u.name ASC
");
$stmt->bind_param('iiiss', $dietitian_id, $perPage, $offset, $start, $end);
$stmt->execute();
$result = $stmt->get_result();

$patients = [];
while ($u = $result->fetch_assoc()) {
    [$bmi] = calculate_bmi($u['height_cm'], $u['weight_kg']);
    $daily_need = daily_calorie_need($u['height_cm'], $u['weight_kg'], $u['age'], $u['gender'], $u['activity_level']);
    $today_intake = (int) round($u['today_intake']);

    if ($daily_need === 0)                       $status = 'N/A';
    elseif ($today_intake > $daily_need * 1.1)   $status = 'Over Eating';
    elseif ($today_intake < $daily_need * 0.8)   $status = 'Under Target';
    else                                         $status = 'Normal';

    $patients[] = [
        'user_id'      => (int) $u['user_id'],
        'name'         => $u['name'],
        'bmi'          => $bmi ?? 0,
        'daily_need'   => $daily_need,
        'today_intake' => $today_intake,
        'status'       => $status,
    ];
}
$stmt->close();

json_response([
    'success'    => true,
    'patients'   => $patients,
    'pagination' => pagination_meta($total, $page, $perPage),
]);

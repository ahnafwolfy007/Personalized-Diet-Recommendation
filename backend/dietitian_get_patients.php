<?php
// backend/dietitian_get_patients.php
// Returns the patients assigned to the logged-in dietitian, with today's intake.
// Today's intake is aggregated in a single query (no per-patient N+1 lookups).

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$dietitian_id = require_role('dietitian');

[$start, $end] = day_bounds(date('Y-m-d'));

$stmt = $conn->prepare("
    SELECT u.user_id, u.name, u.age, u.gender, u.height_cm, u.weight_kg, u.activity_level,
           COALESCE(SUM(fl.calories_consumed), 0) AS today_intake
    FROM users u
    LEFT JOIN food_logs fl
           ON fl.user_id = u.user_id
          AND fl.logged_at >= ? AND fl.logged_at < ?
    WHERE u.assigned_dietitian_id = ? AND u.role = 'patient'
    GROUP BY u.user_id, u.name, u.age, u.gender, u.height_cm, u.weight_kg, u.activity_level
    ORDER BY u.name ASC
");
$stmt->bind_param('ssi', $start, $end, $dietitian_id);
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

json_response(['success' => true, 'patients' => $patients]);

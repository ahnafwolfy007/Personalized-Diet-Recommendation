<?php
// backend/dietitian_get_analytics.php
// Per-patient analytics for the logged-in dietitian's assigned patients:
// today's intake vs need, 7-day average intake, plan adherence (meals ticked
// over the last 7 days), today's water, and last-active time. The patient list
// is paginated; the aggregate queries are then constrained to that page's
// patient ids (one query each — no per-patient N+1).

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$dietitian_id = require_role('dietitian');

[$tStart, $tEnd] = day_bounds(date('Y-m-d'));
$weekStart = date('Y-m-d 00:00:00', strtotime('-6 days'));
[$page, $perPage, $offset] = pagination_args(10);

$total = (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE assigned_dietitian_id = " . (int) $dietitian_id . " AND role = 'patient'")->fetch_assoc()['c'];

// 1) Base list: this page of assigned patients + metrics.
$stmt = $conn->prepare("
    SELECT user_id, name, age, gender, height_cm, weight_kg, activity_level
    FROM users
    WHERE assigned_dietitian_id = ? AND role = 'patient'
    ORDER BY name ASC
    LIMIT ? OFFSET ?
");
$stmt->bind_param('iii', $dietitian_id, $perPage, $offset);
$stmt->execute();
$res = $stmt->get_result();
$patients = [];
while ($u = $res->fetch_assoc()) {
    $patients[(int) $u['user_id']] = [
        'user_id'      => (int) $u['user_id'],
        'name'         => $u['name'],
        'daily_need'   => daily_calorie_need($u['height_cm'], $u['weight_kg'], $u['age'], $u['gender'], $u['activity_level']),
        'today_intake' => 0,
        'avg_intake'   => 0,
        'water_today'  => 0,
        'adherence'    => null,
        'last_active'  => null,
    ];
}
$stmt->close();

if (!empty($patients)) {
    // Safe integer id list (values come straight from the DB).
    $idList = implode(',', array_keys($patients));

    // 2) Food intake: today, 7-day total, last active.
    $stmt = $conn->prepare("
        SELECT user_id,
               COALESCE(SUM(CASE WHEN logged_at >= ? AND logged_at < ? THEN calories_consumed END), 0) AS today_intake,
               COALESCE(SUM(CASE WHEN logged_at >= ? THEN calories_consumed END), 0) AS week_total,
               MAX(logged_at) AS last_active
        FROM food_logs
        WHERE user_id IN ($idList)
        GROUP BY user_id
    ");
    $stmt->bind_param('sss', $tStart, $tEnd, $weekStart);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $id = (int) $row['user_id'];
        if (!isset($patients[$id])) continue;
        $patients[$id]['today_intake'] = (int) round($row['today_intake']);
        $patients[$id]['avg_intake']   = (int) round($row['week_total'] / 7);
        $patients[$id]['last_active']  = $row['last_active'] ? date('M j, g:i A', strtotime($row['last_active'])) : null;
    }
    $stmt->close();

    // 3) Water today.
    $stmt = $conn->prepare("
        SELECT user_id,
               COALESCE(SUM(CASE WHEN entry_type = 'water' AND logged_at >= ? AND logged_at < ? THEN quantity_g END), 0) AS water_today
        FROM food_logs
        WHERE user_id IN ($idList)
        GROUP BY user_id
    ");
    $stmt->bind_param('ss', $tStart, $tEnd);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $id = (int) $row['user_id'];
        if (isset($patients[$id])) $patients[$id]['water_today'] = (int) $row['water_today'];
    }
    $stmt->close();

    // 4) Plan item counts (denominator for adherence).
    $itemCounts = [];
    $res = $conn->query("
        SELECT dp.patient_id, COUNT(i.item_id) AS items
        FROM diet_plans dp
        JOIN diet_plan_items i ON i.plan_id = dp.plan_id
        WHERE dp.patient_id IN ($idList)
        GROUP BY dp.patient_id
    ");
    while ($row = $res->fetch_assoc()) { $itemCounts[(int) $row['patient_id']] = (int) $row['items']; }

    // 5) "Taken" plan-item logs over the last 7 days (numerator for adherence).
    $doneCounts = [];
    $stmt = $conn->prepare("
        SELECT user_id AS patient_id, COUNT(*) AS done
        FROM food_logs
        WHERE user_id IN ($idList) AND plan_item_id IS NOT NULL AND logged_at >= ?
        GROUP BY user_id
    ");
    $stmt->bind_param('s', $weekStart);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $doneCounts[(int) $row['patient_id']] = (int) $row['done']; }
    $stmt->close();

    foreach ($patients as $id => &$p) {
        $items = $itemCounts[$id] ?? 0;
        if ($items > 0) {
            $done = $doneCounts[$id] ?? 0;
            $p['adherence'] = min(100, (int) round(($done / ($items * 7)) * 100));
        }
    }
    unset($p);
}

json_response([
    'success'    => true,
    'patients'   => array_values($patients),
    'pagination' => pagination_meta($total, $page, $perPage),
]);

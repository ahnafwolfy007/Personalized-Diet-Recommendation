<?php
// backend/admin_get_stats.php
// Returns admin dashboard statistics

require_once __DIR__ . '/auth.php';

require_role('admin');

// Counts in a single pass over the users table, plus the diet plan count.
$counts = ['patient' => 0, 'dietitian' => 0];
$res = $conn->query("SELECT role, COUNT(*) AS cnt FROM users WHERE role IN ('patient','dietitian') GROUP BY role");
while ($row = $res->fetch_assoc()) {
    $counts[$row['role']] = (int) $row['cnt'];
}

$res = $conn->query("SELECT COUNT(*) AS cnt FROM diet_plans");
$active_plans = (int) $res->fetch_assoc()['cnt'];

$res = $conn->query("SELECT COUNT(*) AS cnt FROM foods WHERE is_verified = 0");
$pending_foods = (int) $res->fetch_assoc()['cnt'];

json_response([
    'success'          => true,
    'total_patients'   => $counts['patient'],
    'total_dietitians' => $counts['dietitian'],
    'active_plans'     => $active_plans,
    'pending_foods'    => $pending_foods,
]);

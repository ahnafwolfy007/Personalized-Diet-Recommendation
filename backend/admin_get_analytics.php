<?php
// backend/admin_get_analytics.php
// Application-wide statistics for the admin Analytics page.

require_once __DIR__ . '/auth.php';

require_role('admin');

$stats = [
    'total_patients'   => 0,
    'total_dietitians' => 0,
    'active_plans'     => 0,
    'total_foods'      => 0,
    'pending_foods'    => 0,
    'assigned_patients'=> 0,
    'active_patients_7d' => 0,
    'removals'         => 0,
];

// User counts by role.
$res = $conn->query("SELECT role, COUNT(*) AS cnt FROM users WHERE role IN ('patient','dietitian') GROUP BY role");
while ($row = $res->fetch_assoc()) {
    if ($row['role'] === 'patient')   $stats['total_patients']   = (int) $row['cnt'];
    if ($row['role'] === 'dietitian') $stats['total_dietitians'] = (int) $row['cnt'];
}

$stats['active_plans'] = (int) $conn->query("SELECT COUNT(*) AS c FROM diet_plans")->fetch_assoc()['c'];

// Foods.
$res = $conn->query("SELECT COUNT(*) AS total, SUM(is_verified = 0) AS pending FROM foods")->fetch_assoc();
$stats['total_foods']   = (int) $res['total'];
$stats['pending_foods'] = (int) $res['pending'];

// Assigned patients (have a dietitian).
$stats['assigned_patients'] = (int) $conn->query(
    "SELECT COUNT(*) AS c FROM users WHERE role = 'patient' AND assigned_dietitian_id IS NOT NULL"
)->fetch_assoc()['c'];

// Active patients in the last 7 days (logged any food).
$weekStart = date('Y-m-d 00:00:00', strtotime('-6 days'));
$stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) AS c FROM food_logs WHERE logged_at >= ?");
$stmt->bind_param('s', $weekStart);
$stmt->execute();
$stats['active_patients_7d'] = (int) $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// Assignment removals recorded.
$stats['removals'] = (int) $conn->query("SELECT COUNT(*) AS c FROM assignment_removals")->fetch_assoc()['c'];

// 7-day signup history.
$stmt = $conn->prepare("SELECT DATE(created_at) AS d, COUNT(*) AS c FROM users WHERE created_at >= ? GROUP BY DATE(created_at)");
$stmt->bind_param('s', $weekStart);
$stmt->execute();
$res = $stmt->get_result();
$byDay = [];
while ($row = $res->fetch_assoc()) { $byDay[$row['d']] = (int) $row['c']; }
$stmt->close();

$signups = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $signups[] = ['label' => date('D', strtotime($day)), 'count' => $byDay[$day] ?? 0];
}

json_response(['success' => true, 'stats' => $stats, 'signups' => $signups]);

<?php
// backend/admin_get_activity.php
// Paginated feed of meaningful patient/dietitian actions for the admin Activity
// Monitor. Individual food/water log entries are never written to activity_log,
// so the feed stays signal-rich.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

require_role('admin');

[$page, $perPage, $offset] = pagination_args(25);

$total = (int) $conn->query("SELECT COUNT(*) AS c FROM activity_log")->fetch_assoc()['c'];

$stmt = $conn->prepare("
    SELECT a.activity_id, a.actor_role, a.action, a.detail, a.created_at,
           u.name AS actor_name
    FROM activity_log a
    LEFT JOIN users u ON u.user_id = a.user_id
    ORDER BY a.created_at DESC, a.activity_id DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param('ii', $perPage, $offset);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = [
        'actor_name' => $row['actor_name'] ?? '(removed user)',
        'actor_role' => $row['actor_role'],
        'action'     => $row['action'],
        'detail'     => $row['detail'],
        'created_at' => date('M j, Y g:i A', strtotime($row['created_at'])),
    ];
}
$stmt->close();

json_response([
    'success'    => true,
    'activity'   => $rows,
    'pagination' => pagination_meta($total, $page, $perPage),
]);

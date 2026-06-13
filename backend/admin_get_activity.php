<?php
// backend/admin_get_activity.php
// Paginated feed of meaningful patient/dietitian actions for the admin Activity
// Monitor. Individual food/water log entries are never written to activity_log,
// so the feed stays signal-rich.

require_once __DIR__ . '/auth.php';

require_role('admin');

$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;
$limit   = $perPage + 1; // fetch one extra to detect "has more"

$stmt = $conn->prepare("
    SELECT a.activity_id, a.actor_role, a.action, a.detail, a.created_at,
           u.name AS actor_name
    FROM activity_log a
    LEFT JOIN users u ON u.user_id = a.user_id
    ORDER BY a.created_at DESC, a.activity_id DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param('ii', $limit, $offset);
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

$has_more = count($rows) > $perPage;
if ($has_more) array_pop($rows);

json_response(['success' => true, 'activity' => $rows, 'page' => $page, 'has_more' => $has_more]);

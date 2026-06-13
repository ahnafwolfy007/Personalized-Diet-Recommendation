<?php
// backend/admin_get_foods.php
// Returns one page of foods for the admin Foods table. Unverified
// (user-contributed) foods sort first so they can be reviewed. Supports an
// optional ?q= search and ?page= pagination. `pending` is the global count of
// unverified foods (not just this page).

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

require_role('admin');

[$page, $perPage, $offset] = pagination_args(15);
$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

// Global pending count (independent of the current page / filter).
$pending = (int) $conn->query("SELECT COUNT(*) AS c FROM foods WHERE is_verified = 0")->fetch_assoc()['c'];

if ($q !== '') {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM foods WHERE name LIKE ? OR category LIKE ?");
    $countStmt->bind_param('ss', $like, $like);
    $countStmt->execute();
    $total = (int) $countStmt->get_result()->fetch_assoc()['c'];
    $countStmt->close();

    $stmt = $conn->prepare("
        SELECT f.food_id, f.name, f.calories_per_100g, f.category, f.is_verified, f.created_at, u.name AS author
        FROM foods f
        LEFT JOIN users u ON u.user_id = f.created_by
        WHERE f.name LIKE ? OR f.category LIKE ?
        ORDER BY f.is_verified ASC, f.created_at DESC, f.name ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('ssii', $like, $like, $perPage, $offset);
} else {
    $total = (int) $conn->query("SELECT COUNT(*) AS c FROM foods")->fetch_assoc()['c'];

    $stmt = $conn->prepare("
        SELECT f.food_id, f.name, f.calories_per_100g, f.category, f.is_verified, f.created_at, u.name AS author
        FROM foods f
        LEFT JOIN users u ON u.user_id = f.created_by
        ORDER BY f.is_verified ASC, f.created_at DESC, f.name ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('ii', $perPage, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$foods = [];
while ($row = $result->fetch_assoc()) {
    $row['is_verified'] = (int) $row['is_verified'];
    $foods[] = $row;
}
$stmt->close();

json_response([
    'success'    => true,
    'foods'      => $foods,
    'pending'    => $pending,
    'pagination' => pagination_meta($total, $page, $perPage),
]);

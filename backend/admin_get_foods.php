<?php
// backend/admin_get_foods.php
// Lists all foods for the admin Foods page. User-contributed (unverified) foods
// are returned first so they can be reviewed, with the contributor's name.

require_once __DIR__ . '/auth.php';

require_role('admin');

$result = $conn->query("
    SELECT f.food_id, f.name, f.calories_per_100g, f.category, f.is_verified, f.created_at,
           u.name AS author
    FROM foods f
    LEFT JOIN users u ON u.user_id = f.created_by
    ORDER BY f.is_verified ASC, f.created_at DESC, f.name ASC
");

$foods = [];
$pending = 0;
while ($row = $result->fetch_assoc()) {
    $row['is_verified'] = (int) $row['is_verified'];
    if ($row['is_verified'] === 0) $pending++;
    $foods[] = $row;
}

json_response(['success' => true, 'foods' => $foods, 'pending' => $pending]);

<?php
// backend/get_foods.php
// Returns foods for the food picker. For patients, the list is sorted so the
// user's most recently and frequently logged foods come first (smart sorting),
// avoiding repetitive searching. Unverified (user-contributed) foods are
// returned with an is_verified flag so the UI can badge them.

require_once __DIR__ . '/auth.php';

$user_id = require_login();
$role    = $_SESSION['user_role'] ?? '';

if ($role === 'patient') {
    // LEFT JOIN a per-user usage summary (count + most recent log) and order by it
    // first. Still a single query; the (user_id, logged_at) index serves the join.
    $stmt = $conn->prepare("
        SELECT f.food_id, f.name, f.calories_per_100g, f.category, f.is_verified,
               COALESCE(u.uses, 0)       AS uses,
               u.last_logged
        FROM foods f
        LEFT JOIN (
            SELECT food_id, COUNT(*) AS uses, MAX(logged_at) AS last_logged
            FROM food_logs
            WHERE user_id = ? AND entry_type = 'food' AND food_id IS NOT NULL
            GROUP BY food_id
        ) u ON u.food_id = f.food_id
        ORDER BY (u.uses IS NULL), u.last_logged DESC, u.uses DESC, f.category, f.name
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $foods = [];
    while ($row = $result->fetch_assoc()) {
        $row['is_verified'] = (int) $row['is_verified'];
        $row['uses']        = (int) $row['uses'];
        unset($row['last_logged']);
        $foods[] = $row;
    }
    $stmt->close();
} else {
    $result = $conn->query("SELECT food_id, name, calories_per_100g, category, is_verified FROM foods ORDER BY category, name");
    $foods = [];
    while ($row = $result->fetch_assoc()) {
        $row['is_verified'] = (int) $row['is_verified'];
        $foods[] = $row;
    }
}

json_response(['success' => true, 'foods' => $foods]);

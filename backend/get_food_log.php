<?php
// backend/get_food_log.php
// Returns food log entries for the logged-in patient for a given day

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user_id = require_login();

$date = $_GET['date'] ?? date('Y-m-d');
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    $date = date('Y-m-d');
}

[$start, $end] = day_bounds($date);

// Paginate only when a page is requested (the Meal Log page); the Log Food
// "today" list calls without ?page and gets all of the day's entries.
$paginated = isset($_GET['page']);
[$page, $perPage, $offset] = pagination_args(15);

$total = 0;
if ($paginated) {
    $cnt = $conn->prepare("SELECT COUNT(*) AS c FROM food_logs WHERE user_id = ? AND entry_type = 'food' AND logged_at >= ? AND logged_at < ?");
    $cnt->bind_param('iss', $user_id, $start, $end);
    $cnt->execute();
    $total = (int) $cnt->get_result()->fetch_assoc()['c'];
    $cnt->close();

    $stmt = $conn->prepare("
        SELECT fl.log_id, f.name AS food_name, fl.quantity_g, fl.calories_consumed,
               fl.serving_unit, fl.serving_amount, fl.logged_at
        FROM food_logs fl
        JOIN foods f ON fl.food_id = f.food_id
        WHERE fl.user_id = ? AND fl.entry_type = 'food' AND fl.logged_at >= ? AND fl.logged_at < ?
        ORDER BY fl.logged_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('issii', $user_id, $start, $end, $perPage, $offset);
} else {
    $stmt = $conn->prepare("
        SELECT fl.log_id, f.name AS food_name, fl.quantity_g, fl.calories_consumed,
               fl.serving_unit, fl.serving_amount, fl.logged_at
        FROM food_logs fl
        JOIN foods f ON fl.food_id = f.food_id
        WHERE fl.user_id = ? AND fl.entry_type = 'food' AND fl.logged_at >= ? AND fl.logged_at < ?
        ORDER BY fl.logged_at DESC
    ");
    $stmt->bind_param('iss', $user_id, $start, $end);
}
$stmt->execute();
$result = $stmt->get_result();

// Friendly unit labels for the amount column.
$unitNames = ['g' => 'g', 'portion' => 'portion', 'glass' => 'glass', 'tbsp' => 'tbsp', 'tsp' => 'tsp'];

// Format a float without trailing ".0" (e.g. 100.0 -> "100", 1.5 -> "1.5").
$fmtNum = static function ($n): string {
    $n = (float) $n;
    return ($n == (int) $n) ? (string) (int) $n : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
};

$logs = [];
while ($row = $result->fetch_assoc()) {
    $row['time'] = date('h:i A', strtotime($row['logged_at']));

    $unit = $row['serving_unit'] ?? 'g';
    if ($unit === 'g' || $row['serving_amount'] === null) {
        $row['amount_label'] = $fmtNum($row['quantity_g']) . 'g';
    } else {
        $label = $unitNames[$unit] ?? $unit;
        $row['amount_label'] = $fmtNum($row['serving_amount']) . ' ' . $label . ' (' . round($row['quantity_g']) . 'g)';
    }
    $logs[] = $row;
}
$stmt->close();

$payload = ['success' => true, 'logs' => $logs];
if ($paginated) {
    $payload['pagination'] = pagination_meta($total, $page, $perPage);

    // Whole-day summary for the cards (independent of the current page).
    $s = $conn->prepare("SELECT COALESCE(SUM(calories_consumed), 0) AS total_cal FROM food_logs WHERE user_id = ? AND entry_type = 'food' AND logged_at >= ? AND logged_at < ?");
    $s->bind_param('iss', $user_id, $start, $end);
    $s->execute();
    $total_cal = (int) round($s->get_result()->fetch_assoc()['total_cal'] ?? 0);
    $s->close();

    $s = $conn->prepare("
        SELECT f.name AS largest_name, fl.calories_consumed AS largest_cal
        FROM food_logs fl JOIN foods f ON f.food_id = fl.food_id
        WHERE fl.user_id = ? AND fl.entry_type = 'food' AND fl.logged_at >= ? AND fl.logged_at < ?
        ORDER BY fl.calories_consumed DESC LIMIT 1
    ");
    $s->bind_param('iss', $user_id, $start, $end);
    $s->execute();
    $largest = $s->get_result()->fetch_assoc();
    $s->close();

    $payload['summary'] = [
        'entries'      => $total,
        'total_cal'    => $total_cal,
        'largest_name' => $largest['largest_name'] ?? null,
        'largest_cal'  => $largest ? (float) $largest['largest_cal'] : 0,
    ];
}
json_response($payload);

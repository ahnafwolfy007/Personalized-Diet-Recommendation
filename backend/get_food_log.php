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

$stmt = $conn->prepare("
    SELECT fl.log_id, f.name AS food_name, fl.quantity_g, fl.calories_consumed,
           fl.serving_unit, fl.serving_amount, fl.logged_at
    FROM food_logs fl
    JOIN foods f ON fl.food_id = f.food_id
    WHERE fl.user_id = ? AND fl.entry_type = 'food' AND fl.logged_at >= ? AND fl.logged_at < ?
    ORDER BY fl.logged_at DESC
");
$stmt->bind_param('iss', $user_id, $start, $end);
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

json_response(['success' => true, 'logs' => $logs]);

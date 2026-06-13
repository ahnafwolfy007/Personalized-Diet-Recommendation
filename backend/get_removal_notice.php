<?php
// backend/get_removal_notice.php
// GET  : returns the latest unacknowledged assignment-removal notice for the
//        logged-in user when they are the *removed counterpart* (i.e. the other
//        party ended the assignment), so they can see the reason.
// POST : acknowledges a notice (removal_id) so it stops showing.

require_once __DIR__ . '/auth.php';

$user_id = require_role('patient', 'dietitian');
$role    = $_SESSION['user_role'] ?? '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
    $removal_id = intval($_POST['removal_id'] ?? 0);
    if ($removal_id <= 0) {
        json_response(['success' => false, 'message' => 'Invalid notice.']);
    }
    // Only the counterpart (not the initiator) may acknowledge their own notice.
    $stmt = $conn->prepare("
        UPDATE assignment_removals
        SET acknowledged = 1
        WHERE removal_id = ? AND removed_by <> ?
          AND ((removed_by_role = 'patient'   AND dietitian_id = ?)
            OR (removed_by_role = 'dietitian' AND patient_id   = ?))
    ");
    $stmt->bind_param('iiii', $removal_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    $stmt->close();
    json_response(['success' => true]);
}

// GET: find the latest unacknowledged notice addressed to this user.
$stmt = $conn->prepare("
    SELECT ar.removal_id, ar.reason, ar.created_at, ar.removed_by_role,
           initiator.name AS initiator_name
    FROM assignment_removals ar
    JOIN users initiator ON initiator.user_id = ar.removed_by
    WHERE ar.acknowledged = 0
      AND ar.removed_by <> ?
      AND ((ar.removed_by_role = 'patient'   AND ar.dietitian_id = ?)
        OR (ar.removed_by_role = 'dietitian' AND ar.patient_id   = ?))
    ORDER BY ar.created_at DESC
    LIMIT 1
");
$stmt->bind_param('iii', $user_id, $user_id, $user_id);
$stmt->execute();
$notice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$notice) {
    json_response(['success' => true, 'notice' => null]);
}

json_response([
    'success' => true,
    'notice'  => [
        'removal_id'     => (int) $notice['removal_id'],
        'reason'         => $notice['reason'],
        'created_at'     => date('M j, Y', strtotime($notice['created_at'])),
        'initiator_name' => $notice['initiator_name'],
        'initiator_role' => $notice['removed_by_role'],
    ],
]);

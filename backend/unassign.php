<?php
// backend/unassign.php
// A patient or a dietitian ends their assignment, providing a required reason.
// The reason is stored in assignment_removals so the removed counterpart and the
// admin can see why. Clears the assignment and reopens the prior accepted request
// so the patient can request someone again.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user_id = require_role('patient', 'dietitian');
$role    = $_SESSION['user_role'] ?? '';
require_post();
require_csrf();

$reason = trim($_POST['reason'] ?? '');
if ($reason === '') {
    json_response(['success' => false, 'message' => 'Please provide a reason.']);
}
if (mb_strlen($reason) > 1000) {
    json_response(['success' => false, 'message' => 'Reason is too long (1000 characters max).']);
}

// Resolve the (patient_id, dietitian_id) pair and verify the assignment exists.
if ($role === 'patient') {
    $patient_id = $user_id;
    $stmt = $conn->prepare("SELECT assigned_dietitian_id FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $dietitian_id = (int) ($stmt->get_result()->fetch_assoc()['assigned_dietitian_id'] ?? 0);
    $stmt->close();
    if ($dietitian_id <= 0) {
        json_response(['success' => false, 'message' => 'You do not currently have an assigned dietitian.']);
    }
} else { // dietitian
    $dietitian_id = $user_id;
    $patient_id   = intval($_POST['patient_id'] ?? 0);
    if ($patient_id <= 0) {
        json_response(['success' => false, 'message' => 'Invalid patient.']);
    }
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND assigned_dietitian_id = ? AND role = 'patient'");
    $stmt->bind_param('ii', $patient_id, $dietitian_id);
    $stmt->execute();
    $ok = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$ok) {
        json_response(['success' => false, 'message' => 'That patient is not currently assigned to you.'], 403);
    }
}

$conn->begin_transaction();
try {
    // Clear the assignment.
    $stmt = $conn->prepare("UPDATE users SET assigned_dietitian_id = NULL WHERE user_id = ?");
    $stmt->bind_param('i', $patient_id);
    if (!$stmt->execute()) { throw new RuntimeException($stmt->error); }
    $stmt->close();

    // Reopen the prior accepted request so the patient may request again.
    $stmt = $conn->prepare("UPDATE dietitian_requests SET status = 'rejected'
                            WHERE patient_id = ? AND dietitian_id = ? AND status = 'accepted'");
    $stmt->bind_param('ii', $patient_id, $dietitian_id);
    if (!$stmt->execute()) { throw new RuntimeException($stmt->error); }
    $stmt->close();

    // Record the rationale.
    $stmt = $conn->prepare("INSERT INTO assignment_removals (patient_id, dietitian_id, removed_by, removed_by_role, reason)
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('iiiss', $patient_id, $dietitian_id, $user_id, $role, $reason);
    if (!$stmt->execute()) { throw new RuntimeException($stmt->error); }
    $stmt->close();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('unassign failed: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Could not complete the unassignment. Please try again.'], 500);
}

log_activity($conn, $user_id, $role, 'unassign',
    ucfirst($role) . ' ended assignment (patient #' . $patient_id . ', dietitian #' . $dietitian_id . '). Reason: ' . mb_substr($reason, 0, 180));

json_response(['success' => true, 'message' => 'Assignment ended. The reason has been recorded.']);

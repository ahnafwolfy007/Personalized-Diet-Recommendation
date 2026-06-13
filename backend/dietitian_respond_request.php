<?php
// backend/dietitian_respond_request.php
// Dietitian accepts or rejects a patient request

require_once __DIR__ . '/auth.php';

$dietitian_id = require_role('dietitian');
require_post();
require_csrf();

$request_id = intval($_POST['request_id'] ?? 0);
$action     = trim($_POST['action'] ?? ''); // 'accept' or 'reject'

if ($request_id <= 0 || !in_array($action, ['accept', 'reject'], true)) {
    json_response(['success' => false, 'message' => 'Invalid data sent.']);
}

// Ensure the request belongs to this dietitian and is still pending.
$check = $conn->prepare("
    SELECT patient_id FROM dietitian_requests
    WHERE request_id = ? AND dietitian_id = ? AND status = 'pending'
");
$check->bind_param('ii', $request_id, $dietitian_id);
$check->execute();
$req = $check->get_result()->fetch_assoc();
$check->close();

if (!$req) {
    json_response(['success' => false, 'message' => 'Request not found or already responded to.'], 404);
}

$patient_id = (int) $req['patient_id'];
$new_status = ($action === 'accept') ? 'accepted' : 'rejected';

// Update the request and (on accept) the assignment atomically.
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("UPDATE dietitian_requests SET status = ? WHERE request_id = ?");
    $stmt->bind_param('si', $new_status, $request_id);
    if (!$stmt->execute()) { throw new RuntimeException($stmt->error); }
    $stmt->close();

    if ($action === 'accept') {
        $assign = $conn->prepare("UPDATE users SET assigned_dietitian_id = ? WHERE user_id = ?");
        $assign->bind_param('ii', $dietitian_id, $patient_id);
        if (!$assign->execute()) { throw new RuntimeException($assign->error); }
        $assign->close();
    }

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('respond_request failed: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Could not process the request. Please try again.'], 500);
}

$message = $action === 'accept'
    ? 'Patient accepted! They are now assigned to you.'
    : 'Request rejected.';

json_response(['success' => true, 'message' => $message]);

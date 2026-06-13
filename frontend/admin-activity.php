<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('admin');
$pageTitle = 'DietSync – Activity Monitor';
$navRole   = 'admin';
$navActive = 'activity';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="max-w-1200">
      <h1 class="text-3xl mb-2">Activity Monitor</h1>
      <p class="text-gray mb-8">Patient and dietitian actions. Individual food and water log entries are excluded to keep the feed meaningful.</p>

      <div class="bg-white border rounded-lg">
        <div class="overflow-x-auto">
          <table>
            <thead>
              <tr><th>When</th><th>User</th><th>Role</th><th>Action</th><th>Details</th></tr>
            </thead>
            <tbody id="activity-table"><tr><td colspan="5" class="text-center text-gray">Loading…</td></tr></tbody>
          </table>
        </div>
        <div class="table-footer" id="activity-pagination"></div>
      </div>
    </div>
  </main>
</div>

<script>
  var ACTION_LABELS = {
    login: 'Logged in', register: 'Registered',
    send_request: 'Sent request', accept_request: 'Accepted patient', reject_request: 'Rejected request',
    plan_created: 'Created plan', plan_updated: 'Updated plan',
    send_feedback: 'Sent feedback', respond_feedback: 'Answered feedback',
    add_food: 'Added food', food_update: 'Edited food', food_delete: 'Deleted food',
    unassign: 'Ended assignment'
  };

  function actionBadge(action) {
    var label = ACTION_LABELS[action] || action;
    var cls = 'badge-gray';
    if (action === 'unassign' || action === 'reject_request' || action === 'food_delete') cls = 'badge-red';
    else if (action === 'register' || action === 'accept_request' || action.indexOf('plan_') === 0) cls = 'badge-green';
    else if (action === 'add_food') cls = 'badge-yellow';
    else if (action === 'login') cls = 'badge-blue';
    return '<span class="badge ' + cls + '">' + escapeHtml(label) + '</span>';
  }

  function load(page) {
    fetch('../backend/admin_get_activity.php?page=' + (page || 1))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) { window.location.href = 'login.php'; return; }
        renderPagination(document.getElementById('activity-pagination'), data.pagination, load);
        var tbody = document.getElementById('activity-table');
        if (data.activity.length === 0) {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray">No activity recorded yet.</td></tr>';
          return;
        }
        tbody.innerHTML = data.activity.map(function (a) {
          return '<tr>' +
            '<td class="text-gray">' + escapeHtml(a.created_at) + '</td>' +
            '<td>' + escapeHtml(a.actor_name) + '</td>' +
            '<td class="text-gray">' + escapeHtml(a.actor_role) + '</td>' +
            '<td>' + actionBadge(a.action) + '</td>' +
            '<td class="text-gray">' + escapeHtml(a.detail || '—') + '</td>' +
            '</tr>';
        }).join('');
      })
      .catch(function () {
        document.getElementById('activity-table').innerHTML =
          '<tr><td colspan="5" class="text-center text-gray">Could not load activity.</td></tr>';
      });
  }

  load(1);
</script>
</body>
</html>

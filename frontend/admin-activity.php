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
        <div class="p-4 border-t flex items-center justify-between">
          <button id="prev-btn" class="btn btn-secondary btn-sm" disabled>← Newer</button>
          <span id="page-label" class="text-sm text-gray">Page 1</span>
          <button id="next-btn" class="btn btn-secondary btn-sm" disabled>Older →</button>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
  var page = 1, hasMore = false;

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

  function load() {
    fetch('../backend/admin_get_activity.php?page=' + page)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) { window.location.href = 'login.php'; return; }
        hasMore = data.has_more;
        var tbody = document.getElementById('activity-table');
        if (data.activity.length === 0) {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray">No activity recorded yet.</td></tr>';
        } else {
          tbody.innerHTML = data.activity.map(function (a) {
            return '<tr>' +
              '<td class="text-gray">' + escapeHtml(a.created_at) + '</td>' +
              '<td>' + escapeHtml(a.actor_name) + '</td>' +
              '<td class="text-gray">' + escapeHtml(a.actor_role) + '</td>' +
              '<td>' + actionBadge(a.action) + '</td>' +
              '<td class="text-gray">' + escapeHtml(a.detail || '—') + '</td>' +
              '</tr>';
          }).join('');
        }
        document.getElementById('page-label').textContent = 'Page ' + page;
        document.getElementById('prev-btn').disabled = page <= 1;
        document.getElementById('next-btn').disabled = !hasMore;
      })
      .catch(function () {
        document.getElementById('activity-table').innerHTML =
          '<tr><td colspan="5" class="text-center text-gray">Could not load activity.</td></tr>';
      });
  }

  document.getElementById('prev-btn').addEventListener('click', function () { if (page > 1) { page--; load(); } });
  document.getElementById('next-btn').addEventListener('click', function () { if (hasMore) { page++; load(); } });

  load();
</script>
</body>
</html>

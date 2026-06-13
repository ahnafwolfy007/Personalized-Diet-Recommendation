<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('dietitian');
$pageTitle = 'DietSync – Patient Analytics';
$navRole   = 'dietitian';
$navActive = 'analytics';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="max-w-1200">
      <h1 class="text-3xl mb-2">Patient Analytics</h1>
      <p class="text-gray mb-8">Track your assigned patients' activity and performance.</p>

      <div class="bg-white border rounded-lg">
        <div class="overflow-x-auto">
          <table>
            <thead>
              <tr>
                <th>Patient</th>
                <th class="text-right">Today</th>
                <th class="text-right">7-day avg</th>
                <th class="text-right">Plan adherence</th>
                <th class="text-right">Water today</th>
                <th>Last active</th>
                <th class="text-right">Profile</th>
              </tr>
            </thead>
            <tbody id="analytics-table">
              <tr><td colspan="7" class="text-center text-gray">Loading…</td></tr>
            </tbody>
          </table>
        </div>
        <div class="table-footer" id="analytics-pagination"></div>
      </div>
    </div>
  </main>
</div>

<script>
  function intakeBadge(intake, need) {
    if (!need) return '<span class="badge badge-gray">N/A</span>';
    var cls = intake > need * 1.1 ? 'badge-red' : (intake < need * 0.8 ? 'badge-yellow' : 'badge-green');
    return '<span class="badge ' + cls + '">' + intake.toLocaleString() + ' / ' + need.toLocaleString() + '</span>';
  }

  function adherenceCell(a) {
    if (a === null || a === undefined) return '<span class="text-gray">No plan</span>';
    var cls = a >= 70 ? 'fill-green' : (a >= 40 ? 'fill-yellow' : 'fill-red');
    return '<div style="display:inline-flex;align-items:center;gap:0.5rem;justify-content:flex-end;">' +
      '<div class="progress-track-sm"><div class="progress-fill ' + cls + '" style="width:' + a + '%;"></div></div>' +
      '<span class="text-sm">' + a + '%</span></div>';
  }

  function loadAnalytics(page) {
    fetch('../backend/dietitian_get_analytics.php?page=' + (page || 1))
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success) { window.location.href = 'login.php'; return; }
      var tbody = document.getElementById('analytics-table');
      renderPagination(document.getElementById('analytics-pagination'), data.pagination, loadAnalytics);
      if (data.patients.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-gray">No assigned patients yet.</td></tr>';
        return;
      }
      tbody.innerHTML = data.patients.map(function (p) {
        return '<tr>' +
          '<td>' + escapeHtml(p.name) + '</td>' +
          '<td class="text-right">' + intakeBadge(p.today_intake, p.daily_need) + '</td>' +
          '<td class="text-right text-gray">' + (p.avg_intake ? p.avg_intake.toLocaleString() + ' kcal' : '—') + '</td>' +
          '<td class="text-right">' + adherenceCell(p.adherence) + '</td>' +
          '<td class="text-right text-gray">' + (p.water_today ? p.water_today + ' ml' : '—') + '</td>' +
          '<td class="text-gray">' + (p.last_active ? escapeHtml(p.last_active) : 'No activity') + '</td>' +
          '<td class="text-right"><a class="btn btn-secondary btn-sm" href="profile-view.php?id=' + Number(p.user_id) + '">View</a></td>' +
          '</tr>';
      }).join('');
    })
    .catch(function () {
      document.getElementById('analytics-table').innerHTML =
        '<tr><td colspan="7" class="text-center text-gray">Could not load analytics.</td></tr>';
    });
  }

  loadAnalytics(1);
</script>
</body>
</html>

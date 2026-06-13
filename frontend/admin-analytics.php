<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('admin');
$pageTitle = 'DietSync – Analytics';
$navRole   = 'admin';
$navActive = 'analytics';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="max-w-1200">
      <h1 class="text-3xl mb-8">Analytics</h1>

      <div class="grid grid-cols-4 gap-6 mb-8">
        <div class="card p-6"><p class="text-gray text-sm mb-1">Total Patients</p><p class="text-4xl" id="s-patients">—</p></div>
        <div class="card p-6"><p class="text-gray text-sm mb-1">Total Dietitians</p><p class="text-4xl" id="s-dietitians">—</p></div>
        <div class="card p-6"><p class="text-gray text-sm mb-1">Active Meal Plans</p><p class="text-4xl" id="s-plans">—</p></div>
        <div class="card p-6"><p class="text-gray text-sm mb-1">Assigned Patients</p><p class="text-4xl" id="s-assigned">—</p></div>
        <div class="card p-6"><p class="text-gray text-sm mb-1">Active Patients (7d)</p><p class="text-4xl" id="s-active">—</p></div>
        <div class="card p-6"><p class="text-gray text-sm mb-1">Total Foods</p><p class="text-4xl" id="s-foods">—</p></div>
        <div class="card p-6"><p class="text-gray text-sm mb-1">Foods Pending Review</p><p class="text-4xl text-yellow" id="s-pending">—</p></div>
        <div class="card p-6"><p class="text-gray text-sm mb-1">Assignment Removals</p><p class="text-4xl" id="s-removals">—</p></div>
      </div>

      <div class="card p-6">
        <h2 class="text-xl mb-4">New sign-ups (last 7 days)</h2>
        <div class="bar-chart" id="signup-chart"></div>
      </div>
    </div>
  </main>
</div>

<script>
  fetch('../backend/admin_get_analytics.php')
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success) { window.location.href = 'login.php'; return; }
      var s = data.stats;
      document.getElementById('s-patients').textContent   = s.total_patients;
      document.getElementById('s-dietitians').textContent = s.total_dietitians;
      document.getElementById('s-plans').textContent      = s.active_plans;
      document.getElementById('s-assigned').textContent   = s.assigned_patients;
      document.getElementById('s-active').textContent     = s.active_patients_7d;
      document.getElementById('s-foods').textContent      = s.total_foods;
      document.getElementById('s-pending').textContent    = s.pending_foods;
      document.getElementById('s-removals').textContent   = s.removals;

      var max = data.signups.reduce(function (m, d) { return Math.max(m, d.count); }, 0) || 1;
      document.getElementById('signup-chart').innerHTML = data.signups.map(function (d) {
        var h = Math.round((d.count / max) * 100);
        return '<div class="bar-chart-col">' +
          '<div class="bar-chart-track"><div class="bar-chart-fill" style="height:' + h + '%;"></div></div>' +
          '<div class="bar-chart-label">' + escapeHtml(d.label) + '</div>' +
          '<div class="bar-chart-value">' + d.count + '</div></div>';
      }).join('');
    })
    .catch(function () { showToast('Could not load analytics.', 'error'); });
</script>
</body>
</html>

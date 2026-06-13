<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('admin');
$pageTitle = 'DietSync – Admin Dashboard';
$navRole   = 'admin';
$navActive = 'dashboard';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="max-w-1200">
      <h1 class="text-3xl mb-8">Admin Dashboard</h1>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-3 gap-6 mb-8">
        <div class="card p-6">
          <div class="icon-box-lg mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <p class="text-gray text-sm mb-1">Total Patients</p>
          <p class="text-4xl mb-2" id="stat-patients">—</p>
          <p class="text-sm text-green">Registered users</p>
        </div>

        <div class="card p-6">
          <div class="icon-box-lg mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><circle cx="19" cy="11" r="2"/></svg>
          </div>
          <p class="text-gray text-sm mb-1">Total Dietitians</p>
          <p class="text-4xl mb-2" id="stat-dietitians">—</p>
          <p class="text-sm text-green">Active dietitians</p>
        </div>

        <div class="card p-6">
          <div class="icon-box-lg mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
          </div>
          <p class="text-gray text-sm mb-1">Active Meal Plans</p>
          <p class="text-4xl mb-2" id="stat-plans">—</p>
          <p class="text-sm text-green">Plans created</p>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="bg-white border rounded-lg">
        <div class="p-6 border-b">
          <h2 class="text-xl">Quick Actions</h2>
        </div>
        <div class="p-6">
          <div class="activity-item">
            <div>
              <p class="font-medium">Manage Users</p>
              <p class="text-sm text-gray">View, inspect and remove system users</p>
            </div>
            <a href="admin-users.php" class="btn btn-primary btn-sm">Go to Users</a>
          </div>
          <div class="activity-item">
            <div>
              <p class="font-medium">Review Foods</p>
              <p class="text-sm text-gray">Verify or edit user-contributed foods <span id="qa-pending" class="badge badge-yellow hidden"></span></p>
            </div>
            <a href="admin-foods.php" class="btn btn-primary btn-sm">Go to Foods</a>
          </div>
          <div class="activity-item">
            <div>
              <p class="font-medium">Analytics</p>
              <p class="text-sm text-gray">App-wide statistics and trends</p>
            </div>
            <a href="admin-analytics.php" class="btn btn-primary btn-sm">View Analytics</a>
          </div>
          <div class="activity-item">
            <div>
              <p class="font-medium">Activity Monitor</p>
              <p class="text-sm text-gray">Track patient and dietitian actions</p>
            </div>
            <a href="admin-activity.php" class="btn btn-primary btn-sm">Open Monitor</a>
          </div>
        </div>
      </div>

    </div>
  </main>

</div>

<script>
  fetch('../backend/admin_get_stats.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.success) { window.location.href = 'login.php'; return; }
      document.getElementById('stat-patients').textContent   = data.total_patients;
      document.getElementById('stat-dietitians').textContent = data.total_dietitians;
      document.getElementById('stat-plans').textContent      = data.active_plans;

      if (data.pending_foods > 0) {
        var pill = document.getElementById('qa-pending');
        pill.textContent = data.pending_foods + ' pending';
        pill.classList.remove('hidden');
      }
    });
</script>
</body>
</html>

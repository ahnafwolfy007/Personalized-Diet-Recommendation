<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('patient');
$pageTitle = 'DietSync – Dashboard';
$navRole   = 'patient';
$navActive = 'dashboard';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="max-w-1200">
      <h1 class="text-3xl mb-8">Dashboard</h1>

      <!-- Summary Cards -->
      <div class="grid grid-cols-4 gap-6 mb-8">
        <div class="card p-6">
          <div class="icon-box mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
          </div>
          <p class="text-gray text-sm mb-1">Daily Calorie Need</p>
          <p class="text-2xl" id="daily-need">—</p>
          <p class="text-xs text-gray mt-1">kcal/day</p>
        </div>

        <div class="card p-6">
          <div class="icon-box mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
          </div>
          <p class="text-gray text-sm mb-1">Today's Intake</p>
          <p class="text-2xl" id="today-intake">—</p>
          <p class="text-xs text-gray mt-1">kcal consumed</p>
        </div>

        <div class="card p-6">
          <div class="icon-box mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          </div>
          <p class="text-gray text-sm mb-1">Remaining Calories</p>
          <p class="text-2xl" id="remaining">—</p>
          <p class="text-xs text-gray mt-1">kcal left</p>
        </div>

        <div class="card p-6">
          <div class="icon-box mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg>
          </div>
          <p class="text-gray text-sm mb-1">BMI</p>
          <p class="text-2xl" id="bmi">—</p>
          <p class="text-xs text-gray mt-1" id="bmi-label">—</p>
        </div>
      </div>

      <!-- Recent Food Logs -->
      <div class="bg-white border rounded-lg">
        <div class="p-6 border-b">
          <h2 class="text-xl">Recent Food Logs</h2>
        </div>
        <div class="overflow-x-auto">
          <table>
            <thead>
              <tr>
                <th>Food Name</th>
                <th>Amount</th>
                <th>Calories</th>
                <th>Time</th>
              </tr>
            </thead>
            <tbody id="food-log-table">
              <tr><td colspan="4" class="text-center text-gray">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>

</div>

<script>
  // Load dashboard data from backend
  fetch('../backend/get_dashboard.php')
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (data.success) {
        document.getElementById('daily-need').textContent   = data.daily_need.toLocaleString();
        document.getElementById('today-intake').textContent = data.today_intake.toLocaleString();
        document.getElementById('remaining').textContent    = data.remaining.toLocaleString();
        document.getElementById('bmi').textContent          = data.bmi || '—';
        document.getElementById('bmi-label').textContent    = data.bmi_label || '—';

        var tbody = document.getElementById('food-log-table');
        if (data.recent_logs.length === 0) {
          tbody.innerHTML = '<tr><td colspan="4" class="text-center text-gray">No food logged today.</td></tr>';
        } else {
          tbody.innerHTML = data.recent_logs.map(function(log) {
            return '<tr><td>' + escapeHtml(log.food_name) + '</td>' +
                   '<td class="text-gray">' + escapeHtml(log.quantity_g) + 'g</td>' +
                   '<td>' + escapeHtml(log.calories_consumed) + ' kcal</td>' +
                   '<td class="text-gray">' + escapeHtml(log.logged_at) + '</td></tr>';
          }).join('');
        }
      } else {
        // Not logged in – redirect to login
        window.location.href = 'login.php';
      }
    })
    .catch(function() {
      document.getElementById('food-log-table').innerHTML =
        '<tr><td colspan="4" class="text-center text-gray">Could not load data. Please try again later.</td></tr>';
    });
</script>
</body>
</html>

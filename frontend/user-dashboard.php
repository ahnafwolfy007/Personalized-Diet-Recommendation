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

      <!-- Greeting -->
      <div class="dash-hero flex items-center justify-between gap-4">
        <div>
          <h1 class="dash-greeting" id="greeting">Dashboard</h1>
          <p class="dash-subtitle" id="today-date"></p>
        </div>
        <a href="user-log-food.php" class="btn btn-primary flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Log Food
        </a>
      </div>

      <!-- Profile completeness nudge (shown only when metrics are missing) -->
      <div id="profile-nudge" class="nudge hidden">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span class="nudge-text">Complete your age, height and weight to get an accurate daily calorie target.</span>
        <a href="user-profile.php" class="btn btn-primary btn-sm">Complete Profile</a>
      </div>

      <!-- Calorie progress -->
      <div class="card calorie-card p-6">
        <div class="flex justify-between items-baseline mb-2">
          <h2 class="text-xl">Today's Calories</h2>
          <span id="cal-summary" class="text-gray text-sm">—</span>
        </div>
        <div class="calorie-bar-track">
          <div class="calorie-bar-fill" id="cal-bar"></div>
        </div>
        <p class="text-sm text-gray mt-2" id="cal-status">—</p>
      </div>

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
        <div class="p-6 border-b flex justify-between items-center">
          <h2 class="text-xl">Recent Food Logs</h2>
          <a href="user-meal-log.php" class="text-green link-clean text-sm">View all →</a>
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
  // Friendly date + time-of-day greeting
  (function () {
    var now = new Date();
    var hour = now.getHours();
    var part = hour < 12 ? 'Good morning' : (hour < 18 ? 'Good afternoon' : 'Good evening');
    document.getElementById('greeting').setAttribute('data-part', part);
    document.getElementById('today-date').textContent =
      now.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' });
  })();

  fetch('../backend/get_dashboard.php')
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (!data.success) { window.location.href = 'login.php'; return; }

      var part = document.getElementById('greeting').getAttribute('data-part');
      var firstName = (data.name || '').split(' ')[0];
      document.getElementById('greeting').textContent = part + (firstName ? ', ' + firstName : '');

      document.getElementById('daily-need').textContent   = data.daily_need.toLocaleString();
      document.getElementById('today-intake').textContent = data.today_intake.toLocaleString();
      document.getElementById('remaining').textContent    = data.remaining.toLocaleString();
      document.getElementById('bmi').textContent          = data.bmi || '—';
      document.getElementById('bmi-label').textContent    = data.bmi_label || '—';

      // Profile nudge when metrics are missing
      if (!data.profile_complete) {
        document.getElementById('profile-nudge').classList.remove('hidden');
      }

      // Calorie progress bar
      var bar     = document.getElementById('cal-bar');
      var summary = document.getElementById('cal-summary');
      var status  = document.getElementById('cal-status');
      if (data.daily_need > 0) {
        var pct = Math.round((data.today_intake / data.daily_need) * 100);
        bar.style.width = Math.min(pct, 100) + '%';
        bar.classList.toggle('over', pct > 100);
        summary.textContent = data.today_intake.toLocaleString() + ' / ' + data.daily_need.toLocaleString() + ' kcal (' + pct + '%)';
        status.textContent = pct > 100
          ? 'You are ' + (data.today_intake - data.daily_need).toLocaleString() + ' kcal over your target.'
          : data.remaining.toLocaleString() + ' kcal remaining today.';
      } else {
        bar.style.width = '0%';
        summary.textContent = 'Set up your profile';
        status.textContent = 'Add your age, height and weight to track progress against a target.';
      }

      // Recent logs
      var tbody = document.getElementById('food-log-table');
      if (data.recent_logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-gray">No food logged today. <a href="user-log-food.php" class="text-green">Log your first meal →</a></td></tr>';
      } else {
        tbody.innerHTML = data.recent_logs.map(function(log) {
          return '<tr><td>' + escapeHtml(log.food_name) + '</td>' +
                 '<td class="text-gray">' + escapeHtml(log.quantity_g) + 'g</td>' +
                 '<td>' + escapeHtml(log.calories_consumed) + ' kcal</td>' +
                 '<td class="text-gray">' + escapeHtml(log.logged_at) + '</td></tr>';
        }).join('');
      }
    })
    .catch(function() {
      document.getElementById('food-log-table').innerHTML =
        '<tr><td colspan="4" class="text-center text-gray">Could not load data. Please try again later.</td></tr>';
    });

  initRemovalNotice();
</script>
</body>
</html>

<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('patient');
$pageTitle = 'DietSync – Daily Report';
$navRole   = 'patient';
$navActive = 'report';
include __DIR__ . '/partials/head.php';
?>
<body>
  <div class="page-wrapper">

    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
      <div class="max-w-1200">
        <div class="flex items-center justify-between mb-8">
          <h1 class="text-3xl">Daily Calorie Report</h1>
          <input type="date" id="report-date" class="border rounded p-2" style="width:auto;">
        </div>

        <!-- Warning Banner (shown if over limit) -->
        <div id="warning-box" class="hidden warning-box mb-6">
          <div class="warning-box-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
          <div>
            <p class="text-red font-medium">Warning: You exceeded your calorie limit today.</p>
            <p class="text-sm mt-1 text-danger-dark" id="over-msg"></p>
          </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-3 gap-6 mb-8">
          <div class="card p-6">
            <div class="icon-box mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
            </div>
            <p class="text-gray text-sm mb-1">Daily Calorie Requirement</p>
            <p class="text-3xl" id="r-daily-need">—</p>
            <p class="text-xs text-gray mt-1">kcal/day</p>
          </div>

          <div class="card p-6">
            <div class="icon-box mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            </div>
            <p class="text-gray text-sm mb-1">Today's Intake</p>
            <p class="text-3xl" id="r-intake">—</p>
            <p class="text-xs text-gray mt-1">kcal consumed</p>
          </div>

          <div class="card p-6">
            <div class="icon-box mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <p class="text-gray text-sm mb-1" id="r-diff-label">Remaining</p>
            <p class="text-3xl" id="r-diff">—</p>
            <p class="text-xs text-gray mt-1">kcal</p>
          </div>
        </div>

        <!-- Progress Bar -->
        <div class="card p-6 mb-8">
          <h2 class="text-xl mb-4">Calorie Progress</h2>
          <div class="flex justify-between text-sm mb-2">
            <span class="text-gray">Daily Goal Progress</span>
            <span id="r-percent" class="text-green">0%</span>
          </div>
          <div class="progress-bar-lg">
            <div class="progress-bar-fill" id="r-bar" style="width:0%;height:100%;border-radius:0.5rem;background-color:#22c55e;transition:width 0.4s;"></div>
          </div>
          <div class="flex justify-between text-sm text-gray mt-2">
            <span>0 kcal</span>
            <span id="r-goal-label">— kcal</span>
          </div>
        </div>

        <!-- Meal Breakdown -->
        <div class="bg-white border rounded-lg">
          <div class="p-6 border-b">
            <h2 class="text-xl">Meal Breakdown</h2>
          </div>
          <div class="overflow-x-auto">
            <table>
              <thead>
                <tr>
                  <th>Meal</th>
                  <th>Food Items</th>
                  <th class="text-right">Calories</th>
                </tr>
              </thead>
              <tbody id="meal-breakdown-table">
                <tr><td colspan="3" class="text-center text-gray">No data for this date.</td></tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </main>

  </div>

  <script>
    // Set today's date as default
    var dateInput = document.getElementById('report-date');
    dateInput.value = new Date().toISOString().split('T')[0];
    dateInput.addEventListener('change', loadReport);

    function loadReport() {
      var date = dateInput.value;
      fetch('../backend/get_report.php?date=' + encodeURIComponent(date))
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.success) {
            window.location.href = 'login.php';
            return;
          }

          document.getElementById('r-daily-need').textContent = data.daily_need.toLocaleString();
          document.getElementById('r-intake').textContent     = data.total_intake.toLocaleString();
          document.getElementById('r-goal-label').textContent = data.daily_need.toLocaleString() + ' kcal';
          document.getElementById('r-percent').textContent    = data.percent + '%';

          // Progress bar
          var bar = document.getElementById('r-bar');
          var pct = Math.min(data.percent, 100);
          bar.style.width = pct + '%';
          bar.style.backgroundColor = data.percent > 100 ? '#ef4444' : '#22c55e';
          document.getElementById('r-percent').style.color = data.percent > 100 ? '#ef4444' : '#22c55e';

          // Over or under
          var diffLabel = document.getElementById('r-diff-label');
          var diffVal   = document.getElementById('r-diff');
          if (data.over_under > 0) {
            diffLabel.textContent = 'Over Limit';
            diffVal.textContent   = '+' + data.over_under;
            diffVal.className     = 'text-3xl text-red';
            // Show warning
            document.getElementById('over-msg').textContent =
              "You've consumed " + data.over_under + " kcal more than your daily requirement.";
            document.getElementById('warning-box').classList.remove('hidden');
          } else {
            diffLabel.textContent = 'Remaining';
            diffVal.textContent   = Math.abs(data.over_under);
            diffVal.className     = 'text-3xl text-green';
            document.getElementById('warning-box').classList.add('hidden');
          }

          // Meal breakdown table
          var tbody = document.getElementById('meal-breakdown-table');
          if (data.meals.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-gray">No food logged for this date.</td></tr>';
          } else {
            var totalCal = 0;
            var rows = data.meals.map(function(m) {
              totalCal += m.total_calories;
              var foods = m.foods.map(function(f) { return escapeHtml(f); }).join(', ');
              return '<tr><td>' + escapeHtml(m.meal) + '</td><td class="text-gray">' +
                     foods + '</td><td class="text-right">' +
                     m.total_calories.toFixed(0) + ' kcal</td></tr>';
            });
            rows.push('<tr class="row-total"><td class="font-medium">Total</td><td></td>' +
                      '<td class="text-right font-medium">' + totalCal.toFixed(0) + ' kcal</td></tr>');
            tbody.innerHTML = rows.join('');
          }
        })
        .catch(function() {
          document.getElementById('r-intake').textContent = 'Error loading';
        });
    }

    loadReport();
  </script>
</body>
</html>

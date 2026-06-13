<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('patient');
$pageTitle = 'DietSync – Water Intake';
$navRole   = 'patient';
$navActive = 'water';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="max-w-1200">
      <div class="flex items-center gap-3 mb-8">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
        <div>
          <h1 class="text-3xl">Water Intake</h1>
          <p class="text-gray text-sm mt-1">Stay hydrated — your dietitian can see your daily totals.</p>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-6 mb-6">
        <!-- Today progress -->
        <div class="card p-6">
          <div class="flex justify-between items-baseline mb-2">
            <h2 class="text-xl">Today</h2>
            <span id="water-summary" class="text-gray text-sm">—</span>
          </div>
          <div class="calorie-bar-track"><div class="calorie-bar-fill" id="water-bar" style="background-color:#3b82f6;"></div></div>
          <p class="text-sm text-gray mt-2" id="water-status">—</p>

          <!-- Quick add -->
          <div class="flex gap-2 mt-4" style="flex-wrap:wrap;">
            <button class="btn btn-secondary btn-sm js-quick" data-ml="250">+ 1 Glass (250ml)</button>
            <button class="btn btn-secondary btn-sm js-quick" data-ml="500">+ Bottle (500ml)</button>
            <button class="btn btn-secondary btn-sm js-quick" data-ml="100">+ 100ml</button>
          </div>
          <div class="flex gap-2 mt-3">
            <input type="number" id="custom-ml" placeholder="Custom ml" min="1" max="5000" style="flex:1;">
            <button id="add-custom" class="btn btn-primary btn-sm">Add</button>
          </div>
        </div>

        <!-- 7-day history -->
        <div class="card p-6">
          <h2 class="text-xl mb-4">Last 7 days</h2>
          <div class="bar-chart" id="water-chart"></div>
        </div>
      </div>

      <!-- Today's entries -->
      <div class="bg-white border rounded-lg">
        <div class="p-6 border-b"><h2 class="text-xl">Today's Entries</h2></div>
        <div class="overflow-x-auto">
          <table>
            <thead><tr><th>Amount</th><th>Time</th><th class="text-right">Remove</th></tr></thead>
            <tbody id="water-table"><tr><td colspan="3" class="text-center text-gray">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
  var GOAL = 2000;

  function renderChart(history) {
    var max = Math.max(GOAL, history.reduce(function (m, d) { return Math.max(m, d.total); }, 0)) || 1;
    document.getElementById('water-chart').innerHTML = history.map(function (d) {
      var h = Math.round((d.total / max) * 100);
      return '<div class="bar-chart-col">' +
        '<div class="bar-chart-track"><div class="bar-chart-fill" style="height:' + h + '%;background-color:#3b82f6;"></div></div>' +
        '<div class="bar-chart-label">' + escapeHtml(d.label) + '</div>' +
        '<div class="bar-chart-value">' + (d.total >= 1000 ? (d.total / 1000).toFixed(1) + 'L' : d.total) + '</div>' +
      '</div>';
    }).join('');
  }

  function load() {
    fetch('../backend/get_water.php')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) { window.location.href = 'login.php'; return; }
        GOAL = data.goal_ml;
        var pct = Math.round((data.today_total / GOAL) * 100);
        document.getElementById('water-bar').style.width = Math.min(pct, 100) + '%';
        document.getElementById('water-summary').textContent = data.today_total + ' / ' + GOAL + ' ml (' + pct + '%)';
        document.getElementById('water-status').textContent = data.today_total >= GOAL
          ? 'Goal reached — nice work!'
          : (GOAL - data.today_total) + ' ml to go.';

        renderChart(data.history);

        var tbody = document.getElementById('water-table');
        if (data.entries.length === 0) {
          tbody.innerHTML = '<tr><td colspan="3" class="text-center text-gray">No water logged today.</td></tr>';
        } else {
          tbody.innerHTML = data.entries.map(function (e) {
            return '<tr><td>' + e.amount_ml + ' ml</td><td class="text-gray">' + escapeHtml(e.time) + '</td>' +
              '<td class="text-right"><button class="row-action js-del" data-id="' + Number(e.water_id) + '" aria-label="Remove entry">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events:none"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>' +
              '</button></td></tr>';
          }).join('');
        }
      })
      .catch(function () { showToast('Could not load water data.', 'error'); });
  }

  function addWater(ml) {
    var fd = new FormData();
    fd.append('amount_ml', ml);
    fetch('../backend/log_water.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) load();
      })
      .catch(function () { showToast('Network error. Please try again.', 'error'); });
  }

  document.querySelectorAll('.js-quick').forEach(function (b) {
    b.addEventListener('click', function () { addWater(b.getAttribute('data-ml')); });
  });
  document.getElementById('add-custom').addEventListener('click', function () {
    var v = parseInt(document.getElementById('custom-ml').value, 10);
    if (!v || v <= 0) { showToast('Enter a valid amount.', 'error'); return; }
    addWater(v);
    document.getElementById('custom-ml').value = '';
  });

  document.getElementById('water-table').addEventListener('click', function (e) {
    var btn = e.target.closest('.js-del'); if (!btn) return;
    var fd = new FormData();
    fd.append('water_id', btn.getAttribute('data-id'));
    fetch('../backend/delete_water.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) load();
      })
      .catch(function () { showToast('Network error. Please try again.', 'error'); });
  });

  load();
</script>
</body>
</html>

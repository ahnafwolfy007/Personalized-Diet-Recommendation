<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('patient');
$pageTitle = 'DietSync – Meal Log';
$navRole   = 'patient';
$navActive = 'meal-log';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="container-narrow">

      <div class="flex items-center gap-3 mb-8">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/></svg>
        <div>
          <h1 class="text-3xl">Meal Log</h1>
          <p class="text-gray text-sm mt-1">Your food entries for the selected day</p>
        </div>
      </div>

      <!-- Date Selector -->
      <div class="bg-white border rounded-lg p-5 mb-6 flex items-center gap-4">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <label class="text-sm text-gray label-inline">Viewing date:</label>
        <input type="date" id="log-date" class="input-inline">
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-3 gap-6 mb-6">
        <div class="card p-6">
          <div class="text-gray text-sm mb-2">Total Entries</div>
          <div class="text-3xl mb-1" id="total-entries">0</div>
          <div class="text-sm text-gray mt-2">food items logged</div>
        </div>
        <div class="card p-6">
          <div class="text-gray text-sm mb-2">Calories Consumed</div>
          <div class="text-3xl mb-1" id="ml-cals">0 <span class="text-lg text-gray">kcal</span></div>
          <div class="text-sm text-gray mt-3">for selected day</div>
        </div>
        <div class="card p-6">
          <div class="text-gray text-sm mb-2">Largest Item</div>
          <div class="text-lg mb-1" id="ml-largest">—</div>
          <div class="text-sm text-gray mt-3">highest calorie food</div>
        </div>
      </div>

      <!-- Food Log List -->
      <div class="bg-white border rounded-lg overflow-hidden mb-6">
        <div class="p-6 border-b">
          <h2 class="text-xl" id="log-date-title">Today's Food Log</h2>
        </div>
        <div class="overflow-x-auto">
          <table>
            <thead>
              <tr>
                <th>Food Name</th>
                <th>Quantity</th>
                <th class="text-right">Calories</th>
                <th>Time</th>
                <th class="text-right">Remove</th>
              </tr>
            </thead>
            <tbody id="ml-table">
              <tr><td colspan="5" class="text-center text-gray">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Notes Section -->
      <div class="card p-6">
        <h3 class="mb-4">Daily Notes</h3>
        <textarea id="notes-area" class="notes-area" placeholder="Add notes about your meals, how you felt, cravings, etc…"></textarea>
        <div class="flex gap-3 mt-4">
          <button onclick="saveNotes()" class="btn btn-primary btn-sm">Save Notes</button>
          <button onclick="document.getElementById('notes-area').value=''" class="btn btn-secondary btn-sm">Clear</button>
        </div>
        <p id="notes-msg" class="text-sm text-green mt-2 hidden">Notes saved on this device.</p>
      </div>

    </div>
  </main>

</div>

<script>
  var dateInput = document.getElementById('log-date');
  dateInput.value = new Date().toISOString().split('T')[0];

  function loadMealLog() {
    var date = dateInput.value;
    document.getElementById('log-date-title').textContent =
      'Food Log – ' + new Date(date + 'T00:00:00').toDateString();

    fetch('../backend/get_food_log.php?date=' + encodeURIComponent(date))
      .then(function(r) { return r.json(); })
      .then(function(data) {
        var tbody = document.getElementById('ml-table');
        if (!data.success || data.logs.length === 0) {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray">No food logged for this date.</td></tr>';
          document.getElementById('total-entries').textContent = 0;
          document.getElementById('ml-cals').innerHTML = '0 <span class="text-lg text-gray">kcal</span>';
          document.getElementById('ml-largest').textContent = '—';
          return;
        }

        var total = 0;
        var largest = { name: '—', cal: 0 };
        tbody.innerHTML = data.logs.map(function(log) {
          total += parseFloat(log.calories_consumed);
          if (parseFloat(log.calories_consumed) > largest.cal) {
            largest = { name: log.food_name, cal: log.calories_consumed };
          }
          return '<tr>' +
            '<td>' + escapeHtml(log.food_name) + '</td>' +
            '<td class="text-gray">' + escapeHtml(log.amount_label) + '</td>' +
            '<td class="text-right">' + escapeHtml(log.calories_consumed) + ' kcal</td>' +
            '<td class="text-gray">' + escapeHtml(log.time) + '</td>' +
            '<td class="text-right"><button class="row-action js-del" data-id="' + Number(log.log_id) + '" title="Remove entry" aria-label="Remove ' + escapeHtml(log.food_name) + '">' +
              '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events:none"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>' +
            '</button></td>' +
            '</tr>';
        }).join('');

        document.getElementById('total-entries').textContent = data.logs.length;
        document.getElementById('ml-cals').innerHTML = total.toFixed(0) + ' <span class="text-lg text-gray">kcal</span>';
        document.getElementById('ml-largest').textContent = largest.name + ' (' + largest.cal + ' kcal)';
      })
      .catch(function() {
        window.location.href = 'login.php';
      });
  }

  function saveNotes() {
    var notes = document.getElementById('notes-area').value;
    var date  = dateInput.value;
    localStorage.setItem('notes_' + date, notes);
    var msg = document.getElementById('notes-msg');
    msg.classList.remove('hidden');
    setTimeout(function() { msg.classList.add('hidden'); }, 2000);
  }

  // Load saved notes from local storage
  function loadNotes() {
    var date  = dateInput.value;
    var saved = localStorage.getItem('notes_' + date);
    document.getElementById('notes-area').value = saved || '';
  }

  // Delete a log entry (delegated)
  document.getElementById('ml-table').addEventListener('click', function(e) {
    var btn = e.target.closest('.js-del');
    if (!btn) return;
    if (!confirm('Remove this entry?')) return;
    var fd = new FormData();
    fd.append('log_id', btn.getAttribute('data-id'));
    fetch('../backend/delete_food_log.php', { method: 'POST', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) loadMealLog();
      })
      .catch(function() { showToast('Network error. Please try again.', 'error'); });
  });

  dateInput.addEventListener('change', function() {
    loadMealLog();
    loadNotes();
  });
  loadMealLog();
  loadNotes();
</script>
</body>
</html>

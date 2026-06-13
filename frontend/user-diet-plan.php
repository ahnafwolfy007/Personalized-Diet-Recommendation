<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('patient');
$pageTitle = 'DietSync – Diet Plan';
$navRole   = 'patient';
$navActive = 'diet-plan';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="max-w-1200">
      <h1 class="text-3xl mb-2">Your Diet Plan</h1>
      <p class="text-gray mb-8" id="dietitian-name">Loading…</p>

      <div id="no-plan" class="hidden card p-8 text-center text-gray">
        <p class="text-xl mb-2">No diet plan assigned yet.</p>
        <p>A dietitian will create your personalized meal plan soon.</p>
      </div>

      <!-- Structured (database-driven) plan -->
      <div id="plan-content" class="hidden">
        <div id="meals"></div>

        <div class="card p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="icon-box">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <h2 class="text-xl">Dietitian Notes</h2>
          </div>
          <div class="note-box">
            <p id="plan-notes" class="text-gray text-relaxed" style="white-space:pre-wrap;"></p>
          </div>
        </div>
      </div>

      <!-- Legacy free-text plan fallback -->
      <div id="legacy-content" class="hidden">
        <div class="card p-6 mb-6">
          <h2 class="text-xl mb-2">Breakfast</h2>
          <div class="note-box"><p id="lg-breakfast" class="text-gray text-relaxed" style="white-space:pre-wrap;"></p></div>
        </div>
        <div class="card p-6 mb-6">
          <h2 class="text-xl mb-2">Lunch</h2>
          <div class="note-box"><p id="lg-lunch" class="text-gray text-relaxed" style="white-space:pre-wrap;"></p></div>
        </div>
        <div class="card p-6 mb-6">
          <h2 class="text-xl mb-2">Dinner</h2>
          <div class="note-box"><p id="lg-dinner" class="text-gray text-relaxed" style="white-space:pre-wrap;"></p></div>
        </div>
        <div class="card p-6">
          <h2 class="text-xl mb-2">Dietitian Notes</h2>
          <div class="note-box"><p id="lg-notes" class="text-gray text-relaxed" style="white-space:pre-wrap;"></p></div>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
  var MEAL_LABELS = { breakfast: 'Breakfast', lunch: 'Lunch', dinner: 'Dinner' };
  var UNIT_LABEL = { g: 'g', portion: 'portion', glass: 'glass', tbsp: 'tbsp', tsp: 'tsp' };

  function amountText(it) {
    var unit = it.serving_unit || 'g';
    if (unit === 'g' || it.serving_amount == null) return Math.round(it.quantity_g) + 'g';
    return it.serving_amount + ' ' + (UNIT_LABEL[unit] || unit) + ' (' + Math.round(it.quantity_g) + 'g)';
  }

  function checkIcon() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="pointer-events:none"><polyline points="20 6 9 17 4 12"/></svg>';
  }

  function renderMeals(items) {
    var byMeal = { breakfast: [], lunch: [], dinner: [] };
    items.forEach(function (it) { if (byMeal[it.meal]) byMeal[it.meal].push(it); });

    var html = '';
    ['breakfast', 'lunch', 'dinner'].forEach(function (meal) {
      var list = byMeal[meal];
      var subtotal = list.reduce(function (s, it) { return s + Number(it.calories); }, 0);
      html += '<div class="card p-6 mb-6">' +
        '<div class="flex items-center justify-between mb-4">' +
          '<h2 class="text-xl">' + MEAL_LABELS[meal] + '</h2>' +
          '<span class="text-sm text-gray">' + Math.round(subtotal) + ' kcal</span>' +
        '</div>';

      if (list.length === 0) {
        html += '<p class="text-gray text-sm">No items for this meal.</p>';
      } else {
        list.forEach(function (it) {
          var taken = Number(it.taken_today) === 1;
          html += '<div class="meal-row' + (taken ? ' completed' : '') + '" data-item="' + Number(it.item_id) + '">' +
            '<button class="checkbox-btn js-take' + (taken ? ' checked' : '') + '" ' +
              'data-item="' + Number(it.item_id) + '" data-taken="' + (taken ? '1' : '0') + '" ' +
              'title="Mark as taken" aria-pressed="' + (taken ? 'true' : 'false') + '" aria-label="Mark ' + escapeHtml(it.food_name) + ' as taken">' +
              (taken ? checkIcon() : '') +
            '</button>' +
            '<div style="flex:1;">' +
              '<p class="font-medium">' + escapeHtml(it.food_name) + '</p>' +
              '<p class="text-sm text-gray">' + amountText(it) + ' · ' + Math.round(it.calories) + ' kcal</p>' +
            '</div>' +
          '</div>';
        });
      }
      html += '</div>';
    });
    document.getElementById('meals').innerHTML = html;
  }

  function load() {
    fetch('../backend/get_diet_plan.php')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) {
          document.getElementById('dietitian-name').textContent = '';
          document.getElementById('no-plan').classList.remove('hidden');
          return;
        }
        var plan = data.plan;
        document.getElementById('dietitian-name').textContent = 'Created by Dietitian: ' + plan.dietitian_name;

        if (data.items && data.items.length > 0) {
          renderMeals(data.items);
          document.getElementById('plan-notes').textContent = plan.notes || 'No notes.';
          document.getElementById('plan-content').classList.remove('hidden');
        } else if (plan.breakfast_text || plan.lunch_text || plan.dinner_text) {
          // Legacy text plan.
          document.getElementById('lg-breakfast').textContent = plan.breakfast_text || 'Not specified.';
          document.getElementById('lg-lunch').textContent     = plan.lunch_text     || 'Not specified.';
          document.getElementById('lg-dinner').textContent    = plan.dinner_text    || 'Not specified.';
          document.getElementById('lg-notes').textContent     = plan.notes          || 'No notes.';
          document.getElementById('legacy-content').classList.remove('hidden');
        } else {
          document.getElementById('plan-notes').textContent = plan.notes || 'No notes.';
          document.getElementById('plan-content').classList.remove('hidden');
        }
      })
      .catch(function () { window.location.href = 'login.php'; });
  }

  // Toggle "Taken" (delegated)
  document.getElementById('meals').addEventListener('click', function (e) {
    var btn = e.target.closest('.js-take');
    if (!btn) return;
    var itemId = btn.getAttribute('data-item');
    var currentlyTaken = btn.getAttribute('data-taken') === '1';
    var action = currentlyTaken ? 'untake' : 'take';

    btn.disabled = true;
    var fd = new FormData();
    fd.append('item_id', itemId);
    fd.append('action', action);

    fetch('../backend/log_plan_item.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        btn.disabled = false;
        if (!data.success) { showToast(data.message || 'Could not update.', 'error'); return; }
        var nowTaken = !!data.taken;
        btn.setAttribute('data-taken', nowTaken ? '1' : '0');
        btn.setAttribute('aria-pressed', nowTaken ? 'true' : 'false');
        btn.classList.toggle('checked', nowTaken);
        btn.innerHTML = nowTaken ? checkIcon() : '';
        var row = btn.closest('.meal-row');
        if (row) row.classList.toggle('completed', nowTaken);
        showToast(data.message, 'success');
      })
      .catch(function () { btn.disabled = false; showToast('Network error. Please try again.', 'error'); });
  });

  load();
</script>
</body>
</html>

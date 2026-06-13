<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('dietitian');
$pageTitle = 'DietSync – Create Meal Plan';
$navRole   = 'dietitian';
$navActive = 'create-plan';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="max-w-1200">
      <h1 class="text-3xl mb-2">Create / Edit Meal Plan</h1>
      <p class="text-gray mb-8" style="font-size:0.95rem;">
        Build the plan from foods in the database. The total for the three meals
        must stay within the patient's daily calorie requirement.
      </p>

      <!-- No patients banner -->
      <div id="no-patients-banner" class="hidden card p-8 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 1rem;display:block"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <h2 class="text-xl mb-2" style="color:#555;">No Patients Assigned Yet</h2>
        <p class="text-gray mb-6">When a patient sends a request, accept it on your
          <a href="dietitian-dashboard.php" style="color:#22c55e;font-weight:600;">Dashboard</a> and they will appear here.</p>
        <a href="dietitian-dashboard.php" class="btn btn-primary px-6 py-3">Go to Dashboard</a>
      </div>

      <div id="plan-form-wrapper" class="hidden">
        <!-- Step 1: patient -->
        <div class="card p-6 mb-6">
          <div class="form-group" style="margin-bottom:0;">
            <label for="patient-select">1. Select a patient</label>
            <div class="select-wrapper">
              <select id="patient-select">
                <option value="">-- Select a patient --</option>
              </select>
              <span class="select-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
            </div>
          </div>
        </div>

        <div id="builder" class="hidden">
          <!-- Calorie cap meter -->
          <div class="card p-6 mb-6">
            <div class="flex justify-between items-baseline mb-2">
              <h2 class="text-xl">Plan total</h2>
              <span id="cap-summary" class="text-gray text-sm">—</span>
            </div>
            <div class="calorie-bar-track"><div class="calorie-bar-fill" id="cap-bar"></div></div>
            <p class="text-sm mt-2" id="cap-status"></p>
          </div>

          <!-- Step 2: add foods -->
          <div class="card p-6 mb-6">
            <label class="mb-2" style="display:block;">2. Add foods to the plan</label>
            <div class="plan-add-row">
              <div class="select-wrapper" style="min-width:140px;">
                <select id="meal-select">
                  <option value="breakfast">Breakfast</option>
                  <option value="lunch">Lunch</option>
                  <option value="dinner">Dinner</option>
                </select>
                <span class="select-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
              </div>
              <div class="combo" id="food-combo" style="flex:1; min-width:200px;">
                <input type="text" id="food-search" class="combo-input" placeholder="Search foods…" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="food-options" aria-autocomplete="list">
                <ul class="combo-list hidden" id="food-options" role="listbox" aria-label="Food results"></ul>
              </div>
              <input type="number" id="amount-input" placeholder="amount" min="0.1" step="0.1" value="100" style="width:90px;">
              <div class="select-wrapper" style="min-width:130px;">
                <select id="unit-select">
                  <option value="g">Grams (g)</option>
                  <option value="portion">Portion</option>
                  <option value="glass">Glass</option>
                  <option value="tbsp">Table-spoon</option>
                  <option value="tsp">Tea-spoon</option>
                </select>
                <span class="select-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
              </div>
              <button type="button" id="add-item-btn" class="btn btn-primary">Add</button>
            </div>
            <p class="text-xs text-gray mt-2" id="item-preview">Standard amounts: 1 portion = 150g · 1 glass = 250g · 1 tbsp = 15g · 1 tsp = 5g.</p>
          </div>

          <!-- Items table -->
          <div class="bg-white border rounded-lg mb-6">
            <div class="overflow-x-auto">
              <table>
                <thead>
                  <tr><th>Meal</th><th>Food</th><th class="text-right">Amount</th><th class="text-right">Calories</th><th class="text-right">Remove</th></tr>
                </thead>
                <tbody id="items-table">
                  <tr><td colspan="5" class="text-center text-gray">No foods added yet.</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Notes + water goal + save -->
          <div class="card p-6">
            <div class="form-group">
              <label for="water-goal">3. Daily water goal (ml)</label>
              <input type="number" id="water-goal" min="0" max="10000" step="50" placeholder="e.g. 2000" style="max-width:220px;">
              <p class="text-xs text-gray mt-1">Shown to the patient on their Water Intake page. Leave blank for the default (2000 ml).</p>
            </div>
            <div class="form-group">
              <label for="notes">4. Notes / instructions</label>
              <textarea id="notes" rows="4" placeholder="e.g. Avoid fried food. Eat every 3-4 hours…"></textarea>
            </div>
            <button type="button" id="save-btn" class="btn btn-primary flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
              Save Plan
            </button>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
  // Standard unit→grams factors (must match backend serving_units()).
  var UNIT_GRAMS = { g: 1, portion: 150, glass: 250, tbsp: 15, tsp: 5 };
  var UNIT_LABEL = { g: 'g', portion: 'portion', glass: 'glass', tbsp: 'tbsp', tsp: 'tsp' };

  var allFoods = [], filtered = [], activeIndex = -1, selectedFood = null;
  var planItems = [];  // {meal, food_id, food_name, serving_unit, serving_amount, quantity_g, calories}
  var dailyNeed = 0;

  var searchInput = document.getElementById('food-search');
  var optionsList = document.getElementById('food-options');
  var amountInput = document.getElementById('amount-input');
  var unitSelect  = document.getElementById('unit-select');

  // ── Load assigned patients ──
  fetch('../backend/dietitian_get_all_patients.php')
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success) { window.location.href = 'login.php'; return; }
      if (data.patients.length === 0) {
        document.getElementById('no-patients-banner').classList.remove('hidden');
        return;
      }
      document.getElementById('plan-form-wrapper').classList.remove('hidden');
      var sel = document.getElementById('patient-select');
      sel.innerHTML = '<option value="">-- Select a patient --</option>' +
        data.patients.map(function (p) {
          return '<option value="' + Number(p.user_id) + '">' + escapeHtml(p.name) + ' (' + escapeHtml(p.email) + ')</option>';
        }).join('');
    })
    .catch(function () { showToast('Could not load patients.', 'error'); });

  // ── Load all foods once ──
  fetch('../backend/get_foods.php')
    .then(function (r) { return r.json(); })
    .then(function (data) { if (data.success) allFoods = data.foods; });

  // ── Patient change → load context ──
  document.getElementById('patient-select').addEventListener('change', function () {
    var pid = this.value;
    planItems = [];
    document.getElementById('notes').value = '';
    if (!pid) { document.getElementById('builder').classList.add('hidden'); return; }

    fetch('../backend/dietitian_get_plan.php?patient_id=' + encodeURIComponent(pid))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) { showToast(data.message || 'Could not load plan.', 'error'); return; }
        dailyNeed = Number(data.daily_need) || 0;
        document.getElementById('notes').value = data.notes || '';
        document.getElementById('water-goal').value = data.water_goal_ml || '';
        planItems = (data.items || []).map(function (it) {
          return { meal: it.meal, food_id: Number(it.food_id), food_name: it.food_name,
                   serving_unit: it.serving_unit || 'g',
                   serving_amount: Number(it.serving_amount != null ? it.serving_amount : it.quantity_g),
                   quantity_g: Number(it.quantity_g), calories: Number(it.calories) };
        });
        document.getElementById('builder').classList.remove('hidden');
        renderItems();
      })
      .catch(function () { showToast('Could not load plan.', 'error'); });
  });

  // ── Combobox ──
  function renderOptions(list) {
    optionsList.innerHTML = list.length === 0
      ? '<li class="combo-empty">No matching foods</li>'
      : list.map(function (f, i) {
          var badge = Number(f.is_verified) === 0 ? ' <span class="badge badge-yellow" style="font-size:0.6rem;">Unverified</span>' : '';
          return '<li class="combo-option' + (i === activeIndex ? ' active' : '') + '" role="option" data-i="' + i + '">' +
            '<span>' + escapeHtml(f.name) + badge + '</span>' +
            '<span class="combo-cat">' + escapeHtml(f.calories_per_100g) + ' kcal/100g</span></li>';
        }).join('');
    optionsList.classList.remove('hidden');
    searchInput.setAttribute('aria-expanded', 'true');
  }
  function openSearch(q) {
    q = (q || '').trim().toLowerCase();
    filtered = (q === '' ? allFoods.slice(0, 50)
      : allFoods.filter(function (f) { return f.name.toLowerCase().indexOf(q) !== -1 || f.category.toLowerCase().indexOf(q) !== -1; }).slice(0, 50));
    activeIndex = -1;
    renderOptions(filtered);
  }
  function closeSearch() { optionsList.classList.add('hidden'); searchInput.setAttribute('aria-expanded', 'false'); activeIndex = -1; }
  function chooseFood(f) { selectedFood = f; searchInput.value = f.name; closeSearch(); updatePreview(); }

  searchInput.addEventListener('input', function () { selectedFood = null; openSearch(this.value); updatePreview(); });
  searchInput.addEventListener('focus', function () { openSearch(this.value); });
  searchInput.addEventListener('keydown', function (e) {
    if (optionsList.classList.contains('hidden')) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); activeIndex = Math.min(activeIndex + 1, filtered.length - 1); renderOptions(filtered); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); activeIndex = Math.max(activeIndex - 1, 0); renderOptions(filtered); }
    else if (e.key === 'Enter') { if (activeIndex >= 0 && filtered[activeIndex]) { e.preventDefault(); chooseFood(filtered[activeIndex]); } }
    else if (e.key === 'Escape') { closeSearch(); }
  });
  optionsList.addEventListener('mousedown', function (e) {
    var li = e.target.closest('.combo-option'); if (!li) return; e.preventDefault();
    var idx = parseInt(li.getAttribute('data-i'), 10); if (filtered[idx]) chooseFood(filtered[idx]);
  });
  document.addEventListener('click', function (e) { if (!document.getElementById('food-combo').contains(e.target)) closeSearch(); });
  amountInput.addEventListener('input', updatePreview);
  unitSelect.addEventListener('change', updatePreview);

  function updatePreview() {
    var amount = parseFloat(amountInput.value) || 0;
    var unit = unitSelect.value;
    var grams = amount * (UNIT_GRAMS[unit] || 1);
    var prev = document.getElementById('item-preview');
    if (selectedFood && grams > 0) {
      var cal = (selectedFood.calories_per_100g / 100) * grams;
      var u = unit === 'g' ? amount + 'g' : (amount + ' ' + UNIT_LABEL[unit] + ' = ' + Math.round(grams) + 'g');
      prev.textContent = selectedFood.name + ': ' + u + ' ≈ ' + Math.round(cal) + ' kcal';
    } else { prev.textContent = ''; }
  }

  // ── Add item ──
  document.getElementById('add-item-btn').addEventListener('click', function () {
    var amount = parseFloat(amountInput.value);
    var unit = unitSelect.value;
    if (!selectedFood) { showToast('Search and select a food first.', 'error'); return; }
    if (!amount || amount <= 0) { showToast('Enter a valid amount.', 'error'); return; }
    var grams = amount * (UNIT_GRAMS[unit] || 1);
    var cal = Math.round((selectedFood.calories_per_100g / 100) * grams * 10) / 10;
    planItems.push({ meal: document.getElementById('meal-select').value, food_id: Number(selectedFood.food_id),
      food_name: selectedFood.name, serving_unit: unit, serving_amount: amount, quantity_g: grams, calories: cal });
    selectedFood = null; searchInput.value = ''; updatePreview();
    renderItems();
  });

  // ── Items table + cap meter ──
  var MEAL_LABELS = { breakfast: 'Breakfast', lunch: 'Lunch', dinner: 'Dinner' };
  function renderItems() {
    var tbody = document.getElementById('items-table');
    if (planItems.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray">No foods added yet.</td></tr>';
    } else {
      var order = { breakfast: 0, lunch: 1, dinner: 2 };
      var sorted = planItems.map(function (it, i) { return { it: it, i: i }; })
        .sort(function (a, b) { return order[a.it.meal] - order[b.it.meal]; });
      tbody.innerHTML = sorted.map(function (x) {
        return '<tr>' +
          '<td><span class="badge badge-gray">' + MEAL_LABELS[x.it.meal] + '</span></td>' +
          '<td>' + escapeHtml(x.it.food_name) + '</td>' +
          '<td class="text-right text-gray">' +
            (x.it.serving_unit === 'g'
              ? Math.round(x.it.quantity_g) + 'g'
              : x.it.serving_amount + ' ' + (UNIT_LABEL[x.it.serving_unit] || x.it.serving_unit) + ' (' + Math.round(x.it.quantity_g) + 'g)') +
          '</td>' +
          '<td class="text-right">' + Math.round(x.it.calories) + ' kcal</td>' +
          '<td class="text-right"><button class="row-action js-remove" data-i="' + x.i + '" aria-label="Remove ' + escapeHtml(x.it.food_name) + '">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events:none"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>' +
          '</button></td></tr>';
      }).join('');
    }
    updateCap();
  }

  document.getElementById('items-table').addEventListener('click', function (e) {
    var btn = e.target.closest('.js-remove'); if (!btn) return;
    planItems.splice(parseInt(btn.getAttribute('data-i'), 10), 1);
    renderItems();
  });

  function updateCap() {
    var total = planItems.reduce(function (s, it) { return s + Number(it.calories); }, 0);
    var bar = document.getElementById('cap-bar');
    var summary = document.getElementById('cap-summary');
    var status = document.getElementById('cap-status');
    var over = dailyNeed > 0 && total > dailyNeed;

    if (dailyNeed > 0) {
      var pct = Math.round((total / dailyNeed) * 100);
      bar.style.width = Math.min(pct, 100) + '%';
      bar.classList.toggle('over', over);
      summary.textContent = Math.round(total) + ' / ' + dailyNeed + ' kcal (' + pct + '%)';
      status.textContent = over
        ? 'Over the patient\'s requirement by ' + Math.round(total - dailyNeed) + ' kcal — reduce amounts before saving.'
        : (dailyNeed - total) + ' kcal of headroom remaining.';
      status.className = 'text-sm mt-2 ' + (over ? 'text-red' : 'text-gray');
    } else {
      bar.style.width = '0%';
      summary.textContent = Math.round(total) + ' kcal';
      status.textContent = 'This patient has no calculated calorie requirement (incomplete profile), so no cap is enforced.';
      status.className = 'text-sm mt-2 text-gray';
    }
    document.getElementById('save-btn').disabled = over;
  }

  // ── Save ──
  document.getElementById('save-btn').addEventListener('click', function () {
    var pid = document.getElementById('patient-select').value;
    if (!pid) { showToast('Select a patient first.', 'error'); return; }
    if (planItems.length === 0) { showToast('Add at least one food.', 'error'); return; }
    var total = planItems.reduce(function (s, it) { return s + Number(it.calories); }, 0);
    if (dailyNeed > 0 && total > dailyNeed) { showToast('Total exceeds the patient\'s requirement.', 'error'); return; }

    var btn = this; btn.disabled = true; btn.textContent = 'Saving…';
    var fd = new FormData();
    fd.append('patient_id', pid);
    fd.append('notes', document.getElementById('notes').value);
    fd.append('water_goal_ml', document.getElementById('water-goal').value || '');
    fd.append('items', JSON.stringify(planItems.map(function (it) {
      return { meal: it.meal, food_id: it.food_id, serving_unit: it.serving_unit, serving_amount: it.serving_amount };
    })));

    fetch('../backend/dietitian_create_plan.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        btn.disabled = false; btn.textContent = 'Save Plan';
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) window.scrollTo(0, 0);
      })
      .catch(function () { btn.disabled = false; btn.textContent = 'Save Plan'; showToast('Error saving plan.', 'error'); });
  });
</script>
</body>
</html>

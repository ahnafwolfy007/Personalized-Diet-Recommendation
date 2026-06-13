<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('patient');
$pageTitle = 'DietSync – Log Food';
$navRole   = 'patient';
$navActive = 'log-food';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="max-w-1200">
      <h1 class="text-3xl mb-8">Log Food Intake</h1>

      <div class="grid grid-cols-2 gap-6">

        <!-- Food Logging Form -->
        <div class="card p-6">
          <h2 class="text-xl mb-6">Add Food Entry</h2>

          <div class="form-group">
            <label for="food-search">Food Name</label>
            <div class="combo" id="food-combo">
              <input type="text" id="food-search" class="combo-input" placeholder="Search foods…"
                     autocomplete="off" role="combobox" aria-expanded="false"
                     aria-controls="food-options" aria-autocomplete="list">
              <ul class="combo-list hidden" id="food-options" role="listbox" aria-label="Food results"></ul>
            </div>
            <p class="text-xs text-gray mt-1">Start typing to search 200+ foods, or pick from the list on the right.</p>
          </div>

          <div class="form-group">
            <label for="quantity">Quantity (grams)</label>
            <input type="number" id="quantity" value="100" min="1" placeholder="100">
          </div>

          <!-- Live calorie preview -->
          <div id="calc-result" class="hidden mb-4 p-4 bg-gray border rounded-lg">
            <p class="text-sm text-gray mb-1">Estimated Calories</p>
            <p id="calc-calories" class="text-2xl text-green"></p>
            <p id="calc-desc" class="text-sm text-gray mt-1"></p>
          </div>

          <button id="add-btn" class="btn btn-primary btn-w-full py-3 flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Food
          </button>
        </div>

        <!-- Food Database Table -->
        <div class="bg-white border rounded-lg">
          <div class="p-6 border-b">
            <h2 class="text-xl">Food Database</h2>
            <p class="text-sm text-gray mt-1">Click a food to select it · calories per 100g</p>
          </div>
          <div class="overflow-x-auto" style="max-height:500px; overflow-y:auto;">
            <table>
              <thead>
                <tr>
                  <th>Food Name</th>
                  <th>Category</th>
                  <th class="text-right">Calories (per 100g)</th>
                </tr>
              </thead>
              <tbody id="food-db-table">
                <tr><td colspan="3" class="text-center text-gray">Loading…</td></tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Today's logged entries -->
      <div class="bg-white border rounded-lg mt-6">
        <div class="p-6 border-b flex justify-between items-center">
          <h2 class="text-xl">Today's Food Log</h2>
          <span id="total-today" class="text-green font-medium"></span>
        </div>
        <div class="overflow-x-auto">
          <table>
            <thead>
              <tr>
                <th>Food</th>
                <th>Amount</th>
                <th class="text-right">Calories</th>
                <th>Time</th>
                <th class="text-right">Remove</th>
              </tr>
            </thead>
            <tbody id="today-log-table">
              <tr><td colspan="5" class="text-center text-gray">No food logged today.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>

</div>

<script>
  var allFoods = [];
  var selectedFood = null;
  var activeIndex = -1;
  var filtered = [];

  var searchInput = document.getElementById('food-search');
  var optionsList = document.getElementById('food-options');

  // Load all foods from backend
  fetch('../backend/get_foods.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.success) return;
      allFoods = data.foods;

      var tbody = document.getElementById('food-db-table');
      tbody.innerHTML = data.foods.map(function(f) {
        return '<tr class="food-row" data-id="' + Number(f.food_id) + '" style="cursor:pointer">' +
               '<td>' + escapeHtml(f.name) + '</td><td class="text-gray">' + escapeHtml(f.category) + '</td>' +
               '<td class="text-right">' + escapeHtml(f.calories_per_100g) + ' kcal</td></tr>';
      }).join('');
    })
    .catch(function() {
      document.getElementById('food-db-table').innerHTML =
        '<tr><td colspan="3" class="text-center text-gray">Could not load foods.</td></tr>';
    });

  // ── Combobox search ─────────────────────────────────
  function renderOptions(list) {
    if (list.length === 0) {
      optionsList.innerHTML = '<li class="combo-empty">No matching foods</li>';
    } else {
      optionsList.innerHTML = list.map(function(f, i) {
        return '<li class="combo-option' + (i === activeIndex ? ' active' : '') + '" role="option" data-i="' + i + '" aria-selected="' + (i === activeIndex) + '">' +
               '<span>' + escapeHtml(f.name) + '</span>' +
               '<span class="combo-cat">' + escapeHtml(f.category) + ' · ' + escapeHtml(f.calories_per_100g) + ' kcal</span>' +
               '</li>';
      }).join('');
    }
    optionsList.classList.remove('hidden');
    searchInput.setAttribute('aria-expanded', 'true');
  }

  function openSearch(query) {
    query = (query || '').trim().toLowerCase();
    filtered = (query === '')
      ? allFoods.slice(0, 50)
      : allFoods.filter(function(f) {
          return f.name.toLowerCase().indexOf(query) !== -1 ||
                 f.category.toLowerCase().indexOf(query) !== -1;
        }).slice(0, 50);
    activeIndex = -1;
    renderOptions(filtered);
  }

  function closeSearch() {
    optionsList.classList.add('hidden');
    searchInput.setAttribute('aria-expanded', 'false');
    activeIndex = -1;
  }

  function chooseFood(food) {
    selectedFood = food;
    searchInput.value = food.name;
    closeSearch();
    updateCalcPreview();
  }

  searchInput.addEventListener('input', function() {
    selectedFood = null; // typing invalidates a prior pick
    openSearch(this.value);
    updateCalcPreview();
  });
  searchInput.addEventListener('focus', function() { openSearch(this.value); });

  searchInput.addEventListener('keydown', function(e) {
    if (optionsList.classList.contains('hidden')) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); activeIndex = Math.min(activeIndex + 1, filtered.length - 1); renderOptions(filtered); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); activeIndex = Math.max(activeIndex - 1, 0); renderOptions(filtered); }
    else if (e.key === 'Enter') { if (activeIndex >= 0 && filtered[activeIndex]) { e.preventDefault(); chooseFood(filtered[activeIndex]); } }
    else if (e.key === 'Escape') { closeSearch(); }
  });

  optionsList.addEventListener('mousedown', function(e) {
    // mousedown (not click) so it fires before the input blur
    var li = e.target.closest('.combo-option');
    if (!li) return;
    e.preventDefault();
    var idx = parseInt(li.getAttribute('data-i'), 10);
    if (filtered[idx]) chooseFood(filtered[idx]);
  });

  document.addEventListener('click', function(e) {
    if (!document.getElementById('food-combo').contains(e.target)) closeSearch();
  });

  // Click a row in the food database table to select it
  document.getElementById('food-db-table').addEventListener('click', function(e) {
    var row = e.target.closest('.food-row');
    if (!row) return;
    var id = Number(row.getAttribute('data-id'));
    var food = allFoods.filter(function(f) { return Number(f.food_id) === id; })[0];
    if (food) { chooseFood(food); searchInput.focus(); }
  });

  // ── Live calorie preview ────────────────────────────
  function updateCalcPreview() {
    var qty = parseFloat(document.getElementById('quantity').value) || 0;
    var calPer100 = selectedFood ? parseFloat(selectedFood.calories_per_100g) : 0;
    if (calPer100 > 0 && qty > 0) {
      var cal = ((calPer100 / 100) * qty).toFixed(1);
      document.getElementById('calc-calories').textContent = cal + ' kcal';
      document.getElementById('calc-desc').textContent     = qty + 'g × ' + calPer100 + ' kcal/100g';
      document.getElementById('calc-result').classList.remove('hidden');
    } else {
      document.getElementById('calc-result').classList.add('hidden');
    }
  }
  document.getElementById('quantity').addEventListener('input', updateCalcPreview);

  // ── Add food ────────────────────────────────────────
  document.getElementById('add-btn').addEventListener('click', function() {
    var quantity = document.getElementById('quantity').value;

    if (!selectedFood) { showToast('Please search and select a food first.', 'error'); searchInput.focus(); return; }
    if (!quantity || quantity <= 0) { showToast('Please enter a valid quantity.', 'error'); return; }

    var formData = new FormData();
    formData.append('food_id', selectedFood.food_id);
    formData.append('quantity', quantity);

    fetch('../backend/log_food.php', { method: 'POST', body: formData })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          showToast('Logged ' + selectedFood.name + ' (' + data.calories + ' kcal).', 'success');
          selectedFood = null;
          searchInput.value = '';
          document.getElementById('calc-result').classList.add('hidden');
          loadTodayLog();
        } else {
          showToast(data.message || 'Could not log food.', 'error');
        }
      })
      .catch(function() { showToast('Network error. Please try again.', 'error'); });
  });

  // ── Today's food log ────────────────────────────────
  function loadTodayLog() {
    fetch('../backend/get_food_log.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success) return;
        var tbody = document.getElementById('today-log-table');
        var total = 0;
        if (data.logs.length === 0) {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray">No food logged today.</td></tr>';
          document.getElementById('total-today').textContent = '';
          return;
        }
        tbody.innerHTML = data.logs.map(function(log) {
          total += parseFloat(log.calories_consumed);
          return '<tr><td>' + escapeHtml(log.food_name) + '</td>' +
                 '<td class="text-gray">' + escapeHtml(log.quantity_g) + 'g</td>' +
                 '<td class="text-right">' + escapeHtml(log.calories_consumed) + ' kcal</td>' +
                 '<td class="text-gray">' + escapeHtml(log.time) + '</td>' +
                 '<td class="text-right"><button class="row-action js-del" data-id="' + Number(log.log_id) + '" title="Remove entry" aria-label="Remove ' + escapeHtml(log.food_name) + '">' +
                   '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events:none"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>' +
                 '</button></td></tr>';
        }).join('');
        document.getElementById('total-today').textContent = 'Total: ' + total.toFixed(0) + ' kcal today';
      });
  }

  // Delete a log entry (delegated)
  document.getElementById('today-log-table').addEventListener('click', function(e) {
    var btn = e.target.closest('.js-del');
    if (!btn) return;
    if (!confirm('Remove this entry from today\'s log?')) return;
    var fd = new FormData();
    fd.append('log_id', btn.getAttribute('data-id'));
    fetch('../backend/delete_food_log.php', { method: 'POST', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) loadTodayLog();
      })
      .catch(function() { showToast('Network error. Please try again.', 'error'); });
  });

  loadTodayLog();
</script>
</body>
</html>

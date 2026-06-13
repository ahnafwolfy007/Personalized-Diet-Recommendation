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

          <!-- Success / error message -->
          <div id="log-msg" class="hidden mb-4 p-3 rounded text-sm"></div>

          <div class="form-group">
            <label for="food-select">Food Name</label>
            <div class="select-wrapper">
              <select id="food-select">
                <option value="">Loading foods…</option>
              </select>
              <span class="select-arrow">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
              </span>
            </div>
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
            <p class="text-sm text-gray mt-1">Calories per 100g</p>
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
              </tr>
            </thead>
            <tbody id="today-log-table">
              <tr><td colspan="4" class="text-center text-gray">No food logged today.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>

</div>

<script>
  // Load all foods from backend
  fetch('../backend/get_foods.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.success) return;

      // Populate dropdown
      var select = document.getElementById('food-select');
      select.innerHTML = data.foods.map(function(f) {
        return '<option value="' + f.food_id + '" data-cal="' + f.calories_per_100g + '">' +
               escapeHtml(f.name) + ' (' + escapeHtml(f.category) + ')' + '</option>';
      }).join('');

      // Populate food database table
      var tbody = document.getElementById('food-db-table');
      tbody.innerHTML = data.foods.map(function(f) {
        return '<tr><td>' + escapeHtml(f.name) + '</td><td class="text-gray">' + escapeHtml(f.category) + '</td>' +
               '<td class="text-right">' + escapeHtml(f.calories_per_100g) + ' kcal</td></tr>';
      }).join('');

      // Show initial preview
      updateCalcPreview();
    })
    .catch(function() {
      document.getElementById('food-select').innerHTML = '<option>Error loading foods</option>';
    });

  // Live calorie preview when food or quantity changes
  function updateCalcPreview() {
    var select   = document.getElementById('food-select');
    var qty      = parseFloat(document.getElementById('quantity').value) || 0;
    var option   = select.options[select.selectedIndex];
    var calPer100 = option ? parseFloat(option.getAttribute('data-cal')) : 0;

    if (calPer100 > 0 && qty > 0) {
      var cal = ((calPer100 / 100) * qty).toFixed(1);
      document.getElementById('calc-calories').textContent = cal + ' kcal';
      document.getElementById('calc-desc').textContent     = qty + 'g × ' + calPer100 + ' kcal/100g';
      document.getElementById('calc-result').classList.remove('hidden');
    } else {
      document.getElementById('calc-result').classList.add('hidden');
    }
  }

  document.getElementById('food-select').addEventListener('change', updateCalcPreview);
  document.getElementById('quantity').addEventListener('input', updateCalcPreview);

  // Add food button
  document.getElementById('add-btn').addEventListener('click', function() {
    var food_id  = document.getElementById('food-select').value;
    var quantity = document.getElementById('quantity').value;
    var msgDiv   = document.getElementById('log-msg');

    if (!food_id || quantity <= 0) {
      msgDiv.textContent = 'Please select a food and enter a valid quantity.';
      msgDiv.className = 'mb-4 p-3 rounded text-sm bg-red-light text-red border border-red';
      msgDiv.classList.remove('hidden');
      return;
    }

    var formData = new FormData();
    formData.append('food_id', food_id);
    formData.append('quantity', quantity);

    fetch('../backend/log_food.php', { method: 'POST', body: formData })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          msgDiv.textContent = 'Food logged! Calories added: ' + data.calories + ' kcal';
          msgDiv.className = 'mb-4 p-3 rounded text-sm bg-green-light text-green border border-green';
          loadTodayLog();
        } else {
          msgDiv.textContent = data.message;
          msgDiv.className = 'mb-4 p-3 rounded text-sm bg-red-light text-red border border-red';
        }
        msgDiv.classList.remove('hidden');
      });
  });

  // Load today's food log
  function loadTodayLog() {
    fetch('../backend/get_food_log.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success) return;
        var tbody = document.getElementById('today-log-table');
        var total = 0;
        if (data.logs.length === 0) {
          tbody.innerHTML = '<tr><td colspan="4" class="text-center text-gray">No food logged today.</td></tr>';
          document.getElementById('total-today').textContent = '';
        } else {
          tbody.innerHTML = data.logs.map(function(log) {
            total += parseFloat(log.calories_consumed);
            return '<tr><td>' + escapeHtml(log.food_name) + '</td>' +
                   '<td class="text-gray">' + escapeHtml(log.quantity_g) + 'g</td>' +
                   '<td class="text-right">' + escapeHtml(log.calories_consumed) + ' kcal</td>' +
                   '<td class="text-gray">' + escapeHtml(log.time) + '</td></tr>';
          }).join('');
          document.getElementById('total-today').textContent = 'Total: ' + total.toFixed(0) + ' kcal today';
        }
      });
  }

  loadTodayLog();
</script>
</body>
</html>

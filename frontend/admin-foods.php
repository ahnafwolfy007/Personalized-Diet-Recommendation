<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('admin');
$pageTitle = 'DietSync – Foods';
$navRole   = 'admin';
$navActive = 'foods';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="max-w-1200">
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl">Foods</h1>
        <span id="pending-pill" class="badge badge-yellow hidden"></span>
      </div>

      <!-- Add a food (admin-added foods are verified immediately) -->
      <div class="card p-6 mb-6">
        <div class="flex items-center justify-between">
          <h2 class="text-xl">Add a food</h2>
          <button type="button" id="toggle-add" class="btn-link">+ New food</button>
        </div>
        <div id="add-panel" class="hidden mt-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="form-group">
              <label for="af-name">Food name</label>
              <input type="text" id="af-name" maxlength="150" placeholder="e.g. Grilled paneer">
            </div>
            <div class="form-group">
              <label for="af-cat">Category</label>
              <div class="select-wrapper">
                <select id="af-cat"><?php include __DIR__ . '/partials/category_options.php'; ?></select>
                <span class="select-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Calories for a known amount</label>
            <div class="flex gap-2">
              <input type="number" id="af-amount" value="100" min="0.1" step="0.1" placeholder="amount" style="width:90px;">
              <div class="select-wrapper" style="flex:1;max-width:200px;">
                <select id="af-unit">
                  <option value="g">Grams (g)</option>
                  <option value="portion">Portion</option>
                  <option value="glass">Glass</option>
                  <option value="tbsp">Table-spoon</option>
                  <option value="tsp">Tea-spoon</option>
                </select>
                <span class="select-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
              </div>
              <input type="number" id="af-cals" min="1" step="1" placeholder="kcal" style="width:90px;">
            </div>
            <p class="text-xs text-gray mt-1">Converted to calories per 100g on save.</p>
          </div>
          <button type="button" id="af-save" class="btn btn-primary btn-sm">Add food</button>
        </div>
      </div>

      <div class="bg-white border rounded-lg">
        <div class="p-6 border-b">
          <h2 class="text-xl">Food Database</h2>
          <p class="text-sm text-gray mt-1">User-contributed foods awaiting review appear first and are marked <span class="badge badge-yellow" style="font-size:0.65rem;">Unverified</span>.</p>
          <input type="text" id="food-filter" class="mt-4" placeholder="Filter by name or category…" style="max-width:320px;">
        </div>
        <div class="overflow-x-auto">
          <table>
            <thead>
              <tr>
                <th>Food</th><th>Category</th><th class="text-right">kcal/100g</th>
                <th>Added by</th><th>Status</th><th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody id="foods-table"><tr><td colspan="6" class="text-center text-gray">Loading…</td></tr></tbody>
          </table>
        </div>
        <div class="table-footer" id="foods-pagination"></div>
      </div>
    </div>
  </main>
</div>

<!-- Edit modal -->
<div id="edit-modal" class="modal-overlay">
  <div class="modal-content" style="max-width:480px;">
    <div class="modal-header"><h3>Edit food</h3><button type="button" class="modal-close" id="edit-close">&times;</button></div>
    <div class="modal-body">
      <input type="hidden" id="ef-id">
      <div class="form-group"><label for="ef-name">Name</label><input type="text" id="ef-name" maxlength="150"></div>
      <div class="grid grid-cols-2 gap-4">
        <div class="form-group"><label for="ef-cals">Calories per 100g</label><input type="number" id="ef-cals" min="1" max="1000" step="0.1"></div>
        <div class="form-group"><label for="ef-cat">Category</label>
          <div class="select-wrapper">
            <select id="ef-cat"><?php include __DIR__ . '/partials/category_options.php'; ?></select>
            <span class="select-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
          </div>
        </div>
      </div>
      <label class="flex items-center gap-2" style="font-weight:400;">
        <input type="checkbox" id="ef-verified" style="width:auto;"> Verified (approved nutritional info)
      </label>
      <div class="flex gap-3 mt-4">
        <button id="ef-save" class="btn btn-primary">Save</button>
        <button id="ef-cancel" class="btn btn-secondary">Cancel</button>
      </div>
    </div>
  </div>
</div>

<script>
  var allFoods = [];
  var tbody = document.getElementById('foods-table');

  function statusBadge(f) {
    return Number(f.is_verified) === 1
      ? '<span class="badge badge-green">Verified</span>'
      : '<span class="badge badge-yellow">Unverified</span>';
  }

  function render() {
    if (allFoods.length === 0) { tbody.innerHTML = '<tr><td colspan="6" class="text-center text-gray">No foods found.</td></tr>'; return; }
    tbody.innerHTML = allFoods.map(function (f) {
      var verifyBtn = Number(f.is_verified) === 0
        ? '<button class="btn btn-primary btn-sm js-verify" data-id="' + Number(f.food_id) + '">Verify</button>' : '';
      return '<tr' + (Number(f.is_verified) === 0 ? ' style="background:#fffbeb;"' : '') + '>' +
        '<td>' + escapeHtml(f.name) + '</td>' +
        '<td class="text-gray">' + escapeHtml(f.category) + '</td>' +
        '<td class="text-right">' + escapeHtml(f.calories_per_100g) + '</td>' +
        '<td class="text-gray">' + (f.author ? escapeHtml(f.author) : '—') + '</td>' +
        '<td>' + statusBadge(f) + '</td>' +
        '<td class="text-right" style="white-space:nowrap;">' +
          verifyBtn +
          ' <button class="btn btn-secondary btn-sm js-edit" data-id="' + Number(f.food_id) + '">Edit</button>' +
          ' <button class="btn-icon js-delete" title="Delete" data-id="' + Number(f.food_id) + '" data-name="' + escapeHtml(f.name) + '">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events:none"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>' +
          '</button>' +
        '</td></tr>';
    }).join('');
  }

  var currentPage = 1;
  var searchQuery = '';

  function load(page) {
    currentPage = page || 1;
    var url = '../backend/admin_get_foods.php?page=' + currentPage + '&q=' + encodeURIComponent(searchQuery);
    fetch(url)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) { window.location.href = 'login.php'; return; }
        allFoods = data.foods;
        var pill = document.getElementById('pending-pill');
        if (data.pending > 0) { pill.textContent = data.pending + ' pending review'; pill.classList.remove('hidden'); }
        else { pill.classList.add('hidden'); }
        render();
        renderPagination(document.getElementById('foods-pagination'), data.pagination, load);
      })
      .catch(function () { tbody.innerHTML = '<tr><td colspan="6" class="text-center text-gray">Could not load foods.</td></tr>'; });
  }

  var foodSearchTimer;
  document.getElementById('food-filter').addEventListener('input', function () {
    searchQuery = this.value.trim();
    clearTimeout(foodSearchTimer);
    foodSearchTimer = setTimeout(function () { load(1); }, 300);
  });

  function findFood(id) { return allFoods.filter(function (f) { return Number(f.food_id) === Number(id); })[0]; }

  // Save a food (used by Verify and the Edit modal).
  function saveFood(payload, onDone) {
    var fd = new FormData();
    Object.keys(payload).forEach(function (k) { fd.append(k, payload[k]); });
    fetch('../backend/admin_update_food.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) { load(currentPage); if (onDone) onDone(); }
      })
      .catch(function () { showToast('Network error. Please try again.', 'error'); });
  }

  tbody.addEventListener('click', function (e) {
    var verify = e.target.closest('.js-verify');
    var edit   = e.target.closest('.js-edit');
    var del    = e.target.closest('.js-delete');

    if (verify) {
      var f = findFood(verify.getAttribute('data-id'));
      if (f) saveFood({ food_id: f.food_id, name: f.name, category: f.category, calories_per_100g: f.calories_per_100g, is_verified: 1 });
    } else if (edit) {
      var ef = findFood(edit.getAttribute('data-id'));
      if (!ef) return;
      document.getElementById('ef-id').value = ef.food_id;
      document.getElementById('ef-name').value = ef.name;
      document.getElementById('ef-cals').value = ef.calories_per_100g;
      document.getElementById('ef-cat').value = ef.category;
      document.getElementById('ef-verified').checked = Number(ef.is_verified) === 1;
      document.getElementById('edit-modal').classList.add('open');
    } else if (del) {
      var id = del.getAttribute('data-id'), name = del.getAttribute('data-name');
      if (!confirm('Delete "' + name + '"? This also removes its log/plan references and cannot be undone.')) return;
      var fd = new FormData(); fd.append('food_id', id);
      fetch('../backend/admin_delete_food.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) { showToast(data.message, data.success ? 'success' : 'error'); if (data.success) load(currentPage); })
        .catch(function () { showToast('Network error. Please try again.', 'error'); });
    }
  });

  function closeEdit() { document.getElementById('edit-modal').classList.remove('open'); }
  document.getElementById('edit-close').addEventListener('click', closeEdit);
  document.getElementById('ef-cancel').addEventListener('click', closeEdit);
  document.getElementById('edit-modal').addEventListener('click', function (e) {
    if (e.target === this) closeEdit();
  });
  document.getElementById('ef-save').addEventListener('click', function () {
    saveFood({
      food_id: document.getElementById('ef-id').value,
      name: document.getElementById('ef-name').value.trim(),
      category: document.getElementById('ef-cat').value.trim() || 'General',
      calories_per_100g: document.getElementById('ef-cals').value,
      is_verified: document.getElementById('ef-verified').checked ? 1 : 0
    }, closeEdit);
  });

  // ── Add a food (admin) ──
  document.getElementById('toggle-add').addEventListener('click', function () {
    document.getElementById('add-panel').classList.toggle('hidden');
  });
  document.getElementById('af-save').addEventListener('click', function () {
    var name   = document.getElementById('af-name').value.trim();
    var cat    = document.getElementById('af-cat').value;
    var amount = document.getElementById('af-amount').value;
    var unit   = document.getElementById('af-unit').value;
    var cals   = document.getElementById('af-cals').value;
    if (!name) { showToast('Enter a food name.', 'error'); return; }
    if (!amount || amount <= 0) { showToast('Enter the amount.', 'error'); return; }
    if (!cals || cals <= 0) { showToast('Enter the calories for that amount.', 'error'); return; }

    var fd = new FormData();
    fd.append('name', name);
    fd.append('category', cat);
    fd.append('serving_unit', unit);
    fd.append('serving_amount', amount);
    fd.append('calories', cals);

    var btn = this; btn.disabled = true;
    fetch('../backend/add_food.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        btn.disabled = false;
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) {
          document.getElementById('af-name').value = '';
          document.getElementById('af-cals').value = '';
          document.getElementById('af-amount').value = '100';
          document.getElementById('add-panel').classList.add('hidden');
          load(1);
        }
      })
      .catch(function () { btn.disabled = false; showToast('Network error. Please try again.', 'error'); });
  });

  load(1);
</script>
</body>
</html>

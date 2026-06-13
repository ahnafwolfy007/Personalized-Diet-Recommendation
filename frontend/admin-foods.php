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
        <div class="form-group"><label for="ef-cat">Category</label><input type="text" id="ef-cat" maxlength="50"></div>
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
    var q = document.getElementById('food-filter').value.trim().toLowerCase();
    var list = q === '' ? allFoods : allFoods.filter(function (f) {
      return f.name.toLowerCase().indexOf(q) !== -1 || f.category.toLowerCase().indexOf(q) !== -1;
    });
    if (list.length === 0) { tbody.innerHTML = '<tr><td colspan="6" class="text-center text-gray">No foods found.</td></tr>'; return; }
    tbody.innerHTML = list.map(function (f) {
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

  function load() {
    fetch('../backend/admin_get_foods.php')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) { window.location.href = 'login.php'; return; }
        allFoods = data.foods;
        var pill = document.getElementById('pending-pill');
        if (data.pending > 0) { pill.textContent = data.pending + ' pending review'; pill.classList.remove('hidden'); }
        else { pill.classList.add('hidden'); }
        render();
      })
      .catch(function () { tbody.innerHTML = '<tr><td colspan="6" class="text-center text-gray">Could not load foods.</td></tr>'; });
  }

  document.getElementById('food-filter').addEventListener('input', render);

  function findFood(id) { return allFoods.filter(function (f) { return Number(f.food_id) === Number(id); })[0]; }

  // Save a food (used by Verify and the Edit modal).
  function saveFood(payload, onDone) {
    var fd = new FormData();
    Object.keys(payload).forEach(function (k) { fd.append(k, payload[k]); });
    fetch('../backend/admin_update_food.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) { load(); if (onDone) onDone(); }
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
        .then(function (data) { showToast(data.message, data.success ? 'success' : 'error'); if (data.success) load(); })
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

  load();
</script>
</body>
</html>

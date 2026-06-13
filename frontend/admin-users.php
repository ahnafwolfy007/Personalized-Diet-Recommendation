<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('admin');
$pageTitle = 'DietSync – User Management';
$navRole   = 'admin';
$navActive = 'users';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="max-w-1200">

      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl">User Management</h1>
      </div>

      <div class="bg-white border rounded-lg">
        <div class="p-6 border-b">
          <h2 class="text-xl">All Users</h2>
          <p class="text-sm text-gray mt-1">Manage system users and their roles</p>
          <input type="text" id="user-search" class="mt-4" placeholder="Search by name or email…" style="max-width:320px;">
        </div>
        <div class="overflow-x-auto">
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Role</th>
                <th>Email</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="users-table">
              <tr><td colspan="6" class="text-center text-gray">Loading…</td></tr>
            </tbody>
          </table>
        </div>
        <div class="table-footer" id="users-pagination"></div>
      </div>

    </div>
  </main>

</div>

<script>
  var usersTable = document.getElementById('users-table');
  var currentPage = 1;
  var searchQuery = '';

  function loadUsers(page) {
    currentPage = page || 1;
    var url = '../backend/admin_get_users.php?page=' + currentPage + '&q=' + encodeURIComponent(searchQuery);
    fetch(url)
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success) { window.location.href = 'login.php'; return; }
        renderPagination(document.getElementById('users-pagination'), data.pagination, loadUsers);
        if (data.users.length === 0) {
          usersTable.innerHTML = '<tr><td colspan="6" class="text-center text-gray">No users found.</td></tr>';
          return;
        }

        usersTable.innerHTML = data.users.map(function(u) {
          var roleBadge = u.role === 'dietitian'
            ? '<span class="badge badge-green">Dietitian</span>'
            : '<span class="badge badge-gray">Patient</span>';
          var statusBadge = u.status === 'active'
            ? '<span class="badge badge-green">Active</span>'
            : '<span class="badge badge-red">Inactive</span>';
          var joined = new Date(u.created_at).toLocaleDateString();

          return '<tr>' +
            '<td>' + escapeHtml(u.name) + '</td>' +
            '<td>' + roleBadge + '</td>' +
            '<td class="text-gray">' + escapeHtml(u.email) + '</td>' +
            '<td>' + statusBadge + '</td>' +
            '<td class="text-gray">' + escapeHtml(joined) + '</td>' +
            '<td>' +
              '<div class="flex items-center gap-2">' +
                '<a class="btn btn-secondary btn-sm" href="profile-view.php?id=' + Number(u.user_id) + '">View</a>' +
                '<button class="btn-icon js-delete" title="Delete" data-id="' + Number(u.user_id) + '" data-name="' + escapeHtml(u.name) + '">' +
                  '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events:none"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>' +
                '</button>' +
              '</div>' +
            '</td>' +
            '</tr>';
        }).join('');
      });
  }

  // Delegated delete handler (avoids interpolating user data into inline onclick)
  usersTable.addEventListener('click', function(e) {
    var btn = e.target.closest('.js-delete');
    if (!btn) return;
    deleteUser(btn.getAttribute('data-id'), btn.getAttribute('data-name'));
  });

  function deleteUser(userId, userName) {
    if (!confirm('Are you sure you want to delete "' + userName + '"? This cannot be undone.')) return;

    var formData = new FormData();
    formData.append('user_id', userId);

    fetch('../backend/admin_delete_user.php', { method: 'POST', body: formData })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) loadUsers(currentPage);
      })
      .catch(function() { showToast('Network error. Please try again.', 'error'); });
  }

  // Debounced search resets to page 1.
  var searchTimer;
  document.getElementById('user-search').addEventListener('input', function() {
    searchQuery = this.value.trim();
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() { loadUsers(1); }, 300);
  });

  loadUsers(1);
</script>
</body>
</html>

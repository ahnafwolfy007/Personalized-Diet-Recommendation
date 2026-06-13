<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('dietitian');
$pageTitle = 'DietSync – My Profile';
$navRole   = 'dietitian';
$navActive = 'profile';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="max-w-1200">
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl">My Profile</h1>
        <a id="view-public" href="#" class="btn btn-secondary btn-sm">View public profile</a>
      </div>

      <div id="profile-msg" class="modal-msg mb-4"></div>

      <!-- VIEW MODE -->
      <div id="view-mode">
        <div class="card p-6">
          <h2 class="text-xl mb-6">Professional Information</h2>
          <div class="info-row">
            <svg class="info-row-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <div><p class="info-row-label">Name</p><p class="info-row-value" id="v-name">—</p></div>
          </div>
          <div class="info-row">
            <svg class="info-row-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            <div><p class="info-row-label">Email</p><p class="info-row-value" id="v-email">—</p></div>
          </div>
          <div class="info-row">
            <svg class="info-row-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>
            <div><p class="info-row-label">Works at</p><p class="info-row-value" id="v-works">—</p></div>
          </div>
          <div class="info-row">
            <svg class="info-row-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <div><p class="info-row-label">Experience</p><p class="info-row-value" id="v-exp">—</p></div>
          </div>
          <div class="info-row">
            <svg class="info-row-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15 8.5 22 9.3 17 14 18.5 21 12 17.5 5.5 21 7 14 2 9.3 9 8.5 12 2"/></svg>
            <div><p class="info-row-label">Specialization</p><p class="info-row-value" id="v-spec">—</p></div>
          </div>
          <div class="info-row">
            <svg class="info-row-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <div><p class="info-row-label">Bio</p><p class="info-row-value" id="v-bio" style="white-space:pre-wrap;">—</p></div>
          </div>
        </div>
        <div class="mt-6">
          <button id="edit-btn" class="btn btn-primary px-6 py-3">Edit Profile</button>
        </div>
      </div>

      <!-- EDIT MODE -->
      <div id="edit-mode" class="hidden">
        <div class="card p-6">
          <h2 class="text-xl mb-6">Edit Profile</h2>
          <form id="profile-form">
            <div class="grid grid-cols-2 gap-6 mb-6">
              <div>
                <label for="e-name">Name</label>
                <input type="text" id="e-name" name="name" placeholder="Your full name">
              </div>
              <div>
                <label for="e-works">Works at</label>
                <input type="text" id="e-works" name="works_at" placeholder="City Health Clinic">
              </div>
            </div>
            <div class="grid grid-cols-2 gap-6 mb-6">
              <div>
                <label for="e-exp">Experience (years)</label>
                <input type="number" id="e-exp" name="experience_years" min="0" max="80" placeholder="5">
              </div>
              <div>
                <label for="e-spec">Specialization / Skills</label>
                <input type="text" id="e-spec" name="specialization" placeholder="Weight management…">
              </div>
            </div>
            <div class="form-group">
              <label for="e-bio">Short Bio</label>
              <textarea id="e-bio" name="bio" rows="4" placeholder="A sentence or two about your practice."></textarea>
            </div>
            <div class="flex gap-3">
              <button type="submit" class="btn btn-primary px-6 py-3">Save Changes</button>
              <button type="button" id="cancel-btn" class="btn btn-secondary px-6 py-3">Cancel</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
  var SELF_ID = <?= (int) $authUser['id'] ?>;
  var profileData = {};

  function loadProfile() {
    fetch('../backend/get_profile.php')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) { window.location.href = 'login.php'; return; }
        var p = data.profile;
        profileData = p;
        document.getElementById('v-name').textContent  = p.name || '—';
        document.getElementById('v-email').textContent = p.email || '—';
        document.getElementById('v-works').textContent = p.works_at || '—';
        document.getElementById('v-exp').textContent   = p.experience_years ? p.experience_years + ' years' : '—';
        document.getElementById('v-spec').textContent  = p.specialization || '—';
        document.getElementById('v-bio').textContent   = p.bio || '—';
      })
      .catch(function () { showToast('Could not load your profile.', 'error'); });
  }

  document.getElementById('view-public').addEventListener('click', function (e) {
    e.preventDefault();
    window.location.href = 'profile-view.php?id=' + encodeURIComponent(SELF_ID);
  });

  document.getElementById('edit-btn').addEventListener('click', function () {
    document.getElementById('e-name').value  = profileData.name || '';
    document.getElementById('e-works').value = profileData.works_at || '';
    document.getElementById('e-exp').value   = profileData.experience_years || '';
    document.getElementById('e-spec').value  = profileData.specialization || '';
    document.getElementById('e-bio').value   = profileData.bio || '';
    document.getElementById('view-mode').classList.add('hidden');
    document.getElementById('edit-mode').classList.remove('hidden');
  });

  document.getElementById('cancel-btn').addEventListener('click', function () {
    document.getElementById('edit-mode').classList.add('hidden');
    document.getElementById('view-mode').classList.remove('hidden');
  });

  document.getElementById('profile-form').addEventListener('submit', function (e) {
    e.preventDefault();
    fetch('../backend/update_profile.php', { method: 'POST', body: new FormData(this) })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) {
          document.getElementById('edit-mode').classList.add('hidden');
          document.getElementById('view-mode').classList.remove('hidden');
          loadProfile();
        }
      })
      .catch(function () { showToast('Could not connect to the server.', 'error'); });
  });

  loadProfile();
</script>
</body>
</html>

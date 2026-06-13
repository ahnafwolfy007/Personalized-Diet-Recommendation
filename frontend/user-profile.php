<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('patient');
$pageTitle = 'DietSync – Profile';
$pageCss   = 'user-profile.css';
$navRole   = 'patient';
$navActive = 'profile';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <!-- ═══════════════ MAIN CONTENT ═══════════════ -->
  <main class="main-content">
    <div class="max-w-1200">
      <h1 class="text-3xl mb-8">Profile</h1>

      <!-- Alert message for profile save -->
      <div id="profile-msg" class="modal-msg mb-4"></div>

      <!-- ──────────── VIEW MODE ──────────── -->
      <div id="view-mode">

        <!-- Profile cards row -->
        <div class="grid grid-cols-2 gap-6">

          <!-- Personal Information -->
          <div class="card p-6">
            <h2 class="text-xl mb-6">Personal Information</h2>

            <div class="info-row">
              <svg class="info-row-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <div><p class="info-row-label">Name</p><p class="info-row-value" id="v-name">—</p></div>
            </div>

            <div class="info-row">
              <svg class="info-row-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
              <div><p class="info-row-label">Email</p><p class="info-row-value" id="v-email">—</p></div>
            </div>

            <div class="info-row">
              <svg class="info-row-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <div><p class="info-row-label">Age</p><p class="info-row-value" id="v-age">—</p></div>
            </div>

            <div class="info-row">
              <svg class="info-row-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <div><p class="info-row-label">Gender</p><p class="info-row-value" id="v-gender">—</p></div>
            </div>
          </div>

          <!-- Health Metrics -->
          <div class="card p-6">
            <h2 class="text-xl mb-6">Health Metrics</h2>

            <div class="info-row">
              <svg class="info-row-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.3 8.7 8.7 21.3c-1 1-2.5 1-3.4 0l-2.6-2.6c-1-1-1-2.5 0-3.4L15.3 2.7c1-1 2.5-1 3.4 0l2.6 2.6c1 1 1 2.5 0 3.4Z"/></svg>
              <div><p class="info-row-label">Height</p><p class="info-row-value" id="v-height">—</p></div>
            </div>

            <div class="info-row">
              <svg class="info-row-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="3"/><path d="M6.5 8a2 2 0 0 0-1.905 1.46L2.1 18.5A2 2 0 0 0 4 21h16a2 2 0 0 0 1.925-2.54L19.4 9.5A2 2 0 0 0 17.48 8Z"/></svg>
              <div><p class="info-row-label">Weight</p><p class="info-row-value" id="v-weight">—</p></div>
            </div>

            <div class="info-row">
              <svg class="info-row-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
              <div><p class="info-row-label">BMI</p><p class="info-row-value" id="v-bmi">—</p></div>
            </div>

            <div class="info-row">
              <svg class="info-row-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
              <div><p class="info-row-label">Activity Level</p><p class="info-row-value" id="v-activity">—</p></div>
            </div>
          </div>
        </div>

        <!-- Edit button -->
        <div class="mt-6">
          <button id="edit-btn" class="btn btn-primary px-6 py-3">Edit Profile</button>
        </div>

        <!-- ──── MY DIETITIAN SECTION ──── -->
        <div class="mt-8">
          <h2 class="text-xl mb-4">My Dietitian</h2>

          <!-- STATE 1: Dietitian is assigned (green banner) -->
          <div id="state-assigned" class="assigned-banner hidden">
            <svg class="ab-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
            <div>
              <p class="ab-label">Assigned Dietitian</p>
              <p class="ab-name" id="v-dietitian-name">—</p>
            </div>
          </div>

          <!-- STATE 2: Request is pending (yellow banner) -->
          <div id="state-pending" class="pending-banner hidden">
            <svg class="pb-icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <div>
              <strong>Request Sent</strong> — Waiting for the dietitian to accept your request. Check back soon!
            </div>
          </div>

          <!-- STATE 3: No dietitian, no pending request → show button -->
          <div id="state-none" class="no-dietitian-card hidden">
            <div class="nd-text">
              <h3>No Dietitian Assigned</h3>
              <p>Choose a dietitian to get personalized meal plans and advice.</p>
            </div>
            <!-- This button opens the modal popup -->
            <button id="open-modal-btn" class="btn btn-primary px-6 py-3 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
              Choose Dietitian
            </button>
          </div>

        </div><!-- end My Dietitian section -->

      </div><!-- end view-mode -->


      <!-- ──────────── EDIT MODE ──────────── -->
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
                <label for="e-age">Age</label>
                <input type="number" id="e-age" name="age" min="1" max="120" placeholder="e.g. 25">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
              <div>
                <label for="e-gender">Gender</label>
                <div class="select-wrapper">
                  <select id="e-gender" name="gender">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                  <span class="select-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                  </span>
                </div>
              </div>
              <div>
                <label for="e-activity">Activity Level</label>
                <div class="select-wrapper">
                  <select id="e-activity" name="activity_level">
                    <option value="Sedentary (little or no exercise)">Sedentary (little or no exercise)</option>
                    <option value="Lightly Active (1-3 days/week)">Lightly Active (1-3 days/week)</option>
                    <option value="Moderately Active (3-5 days/week)">Moderately Active (3-5 days/week)</option>
                    <option value="Very Active (6-7 days/week)">Very Active (6-7 days/week)</option>
                    <option value="Extra Active (physical job + exercise)">Extra Active (physical job + exercise)</option>
                  </select>
                  <span class="select-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                  </span>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
              <div>
                <label for="e-height">Height (cm)</label>
                <input type="number" id="e-height" name="height" min="50" max="300" placeholder="e.g. 175">
              </div>
              <div>
                <label for="e-weight">Weight (kg)</label>
                <input type="number" id="e-weight" name="weight" min="10" max="500" step="0.1" placeholder="e.g. 70">
              </div>
            </div>

            <div class="flex gap-3">
              <button type="submit" class="btn btn-primary px-6 py-3">Save Changes</button>
              <button type="button" id="cancel-btn" class="btn btn-secondary px-6 py-3">Cancel</button>
            </div>
          </form>
        </div>
      </div><!-- end edit-mode -->

    </div>
  </main>
</div>


<!-- ═══════════════ CHOOSE DIETITIAN MODAL ═══════════════ -->
<div id="dietitian-modal" class="modal-overlay" onclick="handleOverlayClick(event)">
  <div class="modal-box">

    <!-- Modal Header -->
    <div class="modal-header">
      <h3>Choose a Dietitian</h3>
      <button class="modal-close-btn" onclick="closeModal()" title="Close">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="modal-body">
      <!-- Message shown after clicking "Send Request" -->
      <div id="modal-msg" class="modal-msg"></div>

      <!-- The list of dietitians goes here (filled by JavaScript) -->
      <div id="modal-dietitian-list">
        <p class="text-gray text-center" style="padding: 1.5rem 0;">Loading available dietitians…</p>
      </div>
    </div>

  </div>
</div>


<!-- ═══════════════ JAVASCRIPT ═══════════════ -->
<script>
  // Keep the profile data so the edit form can be pre-filled.
  var profileData = {};
  var hasPendingRequest = false;

  // ── 1. LOAD PROFILE ──────────────────────────────────
  function loadProfile() {
    fetch('../backend/get_profile.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success) {
          window.location.href = 'login.php';
          return;
        }
        var p = data.profile;
        profileData = p;

        document.getElementById('v-name').textContent     = p.name || '—';
        document.getElementById('v-email').textContent    = p.email || '—';
        document.getElementById('v-age').textContent      = p.age ? p.age + ' years' : '—';
        document.getElementById('v-gender').textContent   = p.gender || '—';
        document.getElementById('v-height').textContent   = p.height_cm ? p.height_cm + ' cm' : '—';
        document.getElementById('v-weight').textContent   = p.weight_kg ? p.weight_kg + ' kg' : '—';
        document.getElementById('v-bmi').textContent      = p.bmi ? p.bmi + ' (' + p.bmi_label + ')' : '—';
        document.getElementById('v-activity').textContent = p.activity_level || '—';

        if (p.assigned_dietitian_id) {
          document.getElementById('v-dietitian-name').textContent = p.dietitian_name;
          showState('assigned');
        } else {
          checkPendingRequest();
        }
      })
      .catch(function() {
        alert('Could not connect to the server. Please try again later.');
      });
  }

  // ── 2. CHECK PENDING REQUEST ─────────────────────────
  function checkPendingRequest() {
    fetch('../backend/patient_check_request.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.has_pending) {
          hasPendingRequest = true;
          showState('pending');
        } else {
          hasPendingRequest = false;
          showState('none');
        }
      })
      .catch(function() {
        showState('none');
      });
  }

  // ── 3. SHOW STATE ────────────────────────────────────
  function showState(state) {
    document.getElementById('state-assigned').classList.add('hidden');
    document.getElementById('state-pending').classList.add('hidden');
    document.getElementById('state-none').classList.add('hidden');

    if (state === 'assigned') document.getElementById('state-assigned').classList.remove('hidden');
    if (state === 'pending')  document.getElementById('state-pending').classList.remove('hidden');
    if (state === 'none')     document.getElementById('state-none').classList.remove('hidden');
  }

  // ── 4. OPEN / CLOSE MODAL ────────────────────────────
  document.getElementById('open-modal-btn').addEventListener('click', function() { openModal(); });

  function openModal() {
    document.getElementById('dietitian-modal').classList.add('open');
    var msgDiv = document.getElementById('modal-msg');
    msgDiv.style.display = 'none';
    msgDiv.textContent = '';
    loadDietitianList();
  }

  function closeModal() {
    document.getElementById('dietitian-modal').classList.remove('open');
  }

  function handleOverlayClick(event) {
    if (event.target === document.getElementById('dietitian-modal')) {
      closeModal();
    }
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
  });

  // ── 5. LOAD DIETITIAN LIST ───────────────────────────
  function loadDietitianList() {
    var listDiv = document.getElementById('modal-dietitian-list');
    listDiv.innerHTML = '<p class="text-gray text-center" style="padding:1.5rem 0;">Loading…</p>';

    fetch('../backend/get_dietitians.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success || data.dietitians.length === 0) {
          listDiv.innerHTML = '<p class="text-gray text-center" style="padding:1.5rem 0;">No dietitians available right now.</p>';
          return;
        }

        listDiv.innerHTML = data.dietitians.map(function(d) {
          var initial = escapeHtml(d.name.charAt(0).toUpperCase());
          return '<div class="dietitian-item">' +
            '<div class="di-avatar">' + initial + '</div>' +
            '<div class="di-info">' +
              '<div class="di-name">' + escapeHtml(d.name) + '</div>' +
              '<div class="di-role">Dietitian</div>' +
            '</div>' +
            '<button class="btn-request" onclick="sendRequest(' + Number(d.user_id) + ', this)">Send Request</button>' +
          '</div>';
        }).join('');
      })
      .catch(function() {
        listDiv.innerHTML = '<p class="text-gray text-center" style="padding:1.5rem 0;">Could not load dietitians.</p>';
      });
  }

  // ── 6. SEND REQUEST ──────────────────────────────────
  function sendRequest(dietitianId, button) {
    var msgDiv = document.getElementById('modal-msg');

    button.disabled = true;
    button.textContent = 'Sending…';

    var formData = new FormData();
    formData.append('dietitian_id', dietitianId);

    fetch('../backend/patient_send_request.php', { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      msgDiv.textContent = data.message;
      msgDiv.style.display = 'block';
      msgDiv.style.background = data.success ? '#dcfce7' : '#fee2e2';
      msgDiv.style.color      = data.success ? '#166534' : '#991b1b';
      msgDiv.style.border     = data.success ? '1px solid #86efac' : '1px solid #fca5a5';

      if (data.success) {
        setTimeout(function() {
          closeModal();
          hasPendingRequest = true;
          showState('pending');
        }, 1500);
      } else {
        button.disabled = false;
        button.textContent = 'Send Request';
      }
    })
    .catch(function() {
      msgDiv.textContent = 'Error sending request. Please try again.';
      msgDiv.style.display = 'block';
      button.disabled = false;
      button.textContent = 'Send Request';
    });
  }

  // ── 7. EDIT PROFILE ──────────────────────────────────
  document.getElementById('edit-btn').addEventListener('click', function() {
    document.getElementById('e-name').value   = profileData.name || '';
    document.getElementById('e-age').value    = profileData.age || '';
    document.getElementById('e-height').value = profileData.height_cm || '';
    document.getElementById('e-weight').value = profileData.weight_kg || '';

    var genderSel = document.getElementById('e-gender');
    for (var i = 0; i < genderSel.options.length; i++) {
      if (genderSel.options[i].value === profileData.gender) { genderSel.selectedIndex = i; break; }
    }

    var actSel = document.getElementById('e-activity');
    for (var j = 0; j < actSel.options.length; j++) {
      if (actSel.options[j].value === profileData.activity_level) { actSel.selectedIndex = j; break; }
    }

    document.getElementById('view-mode').classList.add('hidden');
    document.getElementById('edit-mode').classList.remove('hidden');
  });

  document.getElementById('cancel-btn').addEventListener('click', function() {
    document.getElementById('edit-mode').classList.add('hidden');
    document.getElementById('view-mode').classList.remove('hidden');
  });

  // ── 8. SAVE PROFILE ──────────────────────────────────
  document.getElementById('profile-form').addEventListener('submit', function(e) {
    e.preventDefault();

    var msgDiv = document.getElementById('profile-msg');

    fetch('../backend/update_profile.php', { method: 'POST', body: new FormData(this) })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      msgDiv.textContent      = data.message;
      msgDiv.style.display    = 'block';
      msgDiv.style.background = data.success ? '#dcfce7' : '#fee2e2';
      msgDiv.style.color      = data.success ? '#166534' : '#991b1b';
      msgDiv.style.border     = data.success ? '1px solid #86efac' : '1px solid #fca5a5';

      if (data.success) {
        document.getElementById('edit-mode').classList.add('hidden');
        document.getElementById('view-mode').classList.remove('hidden');
        loadProfile();
      }
    })
    .catch(function() {
      msgDiv.textContent      = 'Could not connect to the server. Please try again later.';
      msgDiv.style.display    = 'block';
      msgDiv.style.background = '#fee2e2';
      msgDiv.style.color      = '#991b1b';
    });
  });

  loadProfile();
</script>
</body>
</html>

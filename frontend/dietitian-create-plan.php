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

  <!-- Main Content -->
  <main class="main-content">
    <div class="max-w-1200">
      <h1 class="text-3xl mb-2">Create / Edit Meal Plan</h1>
      <p class="text-gray mb-8" style="font-size:0.95rem;">
        You can only create meal plans for patients who are assigned to you.
        Patients must send you a request from their Profile page first.
      </p>

      <!-- Status message box -->
      <div id="plan-msg" class="hidden mb-4 p-3 rounded text-sm"></div>

      <!-- ── NO PATIENTS banner (shown when no assigned patients) ── -->
      <div id="no-patients-banner" class="hidden card p-8 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 1rem;display:block"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <h2 class="text-xl mb-2" style="color:#555;">No Patients Assigned Yet</h2>
        <p class="text-gray mb-6">
          You don't have any assigned patients yet.<br>
          When a patient sends you a request, go to your
          <a href="dietitian-dashboard.php" style="color:#22c55e; font-weight:600;">Dashboard</a>
          and <strong>Accept</strong> their request. They will then appear here.
        </p>
        <a href="dietitian-dashboard.php" class="btn btn-primary px-6 py-3">Go to Dashboard → Accept Requests</a>
      </div>

      <!-- ── PLAN FORM (shown when patients are available) ── -->
      <div id="plan-form-wrapper" class="hidden card p-6">
        <form id="plan-form">

          <!-- Step 1: Select a patient -->
          <div class="form-group">
            <label for="patient-select">
              1. Select a Patient
              <span style="font-weight:400; color:#666; font-size:0.85rem;">(only your assigned patients are shown)</span>
            </label>
            <div class="select-wrapper">
              <select id="patient-select" name="patient_id" onchange="loadExistingPlan(this.value)">
                <option value="">-- Select a patient --</option>
              </select>
              <span class="select-arrow">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
              </span>
            </div>
          </div>

          <!-- Existing plan notice (filled by JS when a plan already exists) -->
          <div id="existing-plan-notice" class="hidden mb-4 p-3 rounded text-sm"
               style="background:#f0fdf4; color:#166534; border:1px solid #86efac;">
            This patient already has a plan from you. The fields are pre-filled — make your changes and click Save.
          </div>

          <!-- Step 2: Breakfast -->
          <div class="form-group">
            <label for="breakfast">2. Breakfast Plan</label>
            <textarea id="breakfast" name="breakfast" rows="4"
              placeholder="e.g. Oatmeal with fruits, 1 boiled egg, green tea…"></textarea>
          </div>

          <!-- Lunch -->
          <div class="form-group">
            <label for="lunch">3. Lunch Plan</label>
            <textarea id="lunch" name="lunch" rows="4"
              placeholder="e.g. Grilled chicken breast (150g), brown rice (1 cup), salad…"></textarea>
          </div>

          <!-- Dinner -->
          <div class="form-group">
            <label for="dinner">4. Dinner Plan</label>
            <textarea id="dinner" name="dinner" rows="4"
              placeholder="e.g. Steamed fish, vegetables, small portion of rice…"></textarea>
          </div>

          <!-- Notes -->
          <div class="form-group">
            <label for="notes">5. Additional Notes / Instructions</label>
            <textarea id="notes" name="notes" rows="5"
              placeholder="e.g. Avoid fried food. Drink 2L water daily. Target: 1800 kcal/day…"></textarea>
          </div>

          <!-- Submit button -->
          <div class="form-actions">
            <button type="submit" id="save-btn" class="btn btn-primary flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
              Save Plan
            </button>
          </div>

        </form>
      </div>

    </div>
  </main>

</div>

<script>
  // ── 1. LOAD ASSIGNED PATIENTS into the dropdown ──────
  fetch('../backend/dietitian_get_all_patients.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.success) {
        window.location.href = 'login.php';
        return;
      }

      if (data.patients.length === 0) {
        document.getElementById('no-patients-banner').classList.remove('hidden');
        document.getElementById('plan-form-wrapper').classList.add('hidden');
        return;
      }

      document.getElementById('no-patients-banner').classList.add('hidden');
      document.getElementById('plan-form-wrapper').classList.remove('hidden');

      var select = document.getElementById('patient-select');
      var html = '<option value="">-- Select a patient --</option>';
      for (var i = 0; i < data.patients.length; i++) {
        var p = data.patients[i];
        html += '<option value="' + Number(p.user_id) + '">' + escapeHtml(p.name) + ' (' + escapeHtml(p.email) + ')</option>';
      }
      select.innerHTML = html;
    })
    .catch(function() {
      document.getElementById('plan-msg').textContent = 'Could not load patients. Please try again later.';
      document.getElementById('plan-msg').classList.remove('hidden');
    });

  // ── 2. LOAD EXISTING PLAN when a patient is selected ─
  function loadExistingPlan(patientId) {
    document.getElementById('breakfast').value = '';
    document.getElementById('lunch').value     = '';
    document.getElementById('dinner').value    = '';
    document.getElementById('notes').value     = '';
    document.getElementById('existing-plan-notice').classList.add('hidden');

    if (!patientId) return;

    fetch('../backend/get_diet_plan.php?patient_id=' + encodeURIComponent(patientId))
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success && data.plan) {
          document.getElementById('breakfast').value = data.plan.breakfast_text || '';
          document.getElementById('lunch').value     = data.plan.lunch_text     || '';
          document.getElementById('dinner').value    = data.plan.dinner_text    || '';
          document.getElementById('notes').value     = data.plan.notes          || '';
          document.getElementById('existing-plan-notice').classList.remove('hidden');
        }
      });
  }

  // ── 3. SUBMIT the form to save/update the plan ───────
  document.getElementById('plan-form').addEventListener('submit', function(e) {
    e.preventDefault();

    var btn    = document.getElementById('save-btn');
    var msgDiv = document.getElementById('plan-msg');

    var patientId = document.getElementById('patient-select').value;
    if (!patientId) {
      msgDiv.textContent      = 'Please select a patient first.';
      msgDiv.style.background = '#fee2e2';
      msgDiv.style.color      = '#991b1b';
      msgDiv.style.border     = '1px solid #fca5a5';
      msgDiv.classList.remove('hidden');
      return;
    }

    btn.textContent = 'Saving…';
    btn.disabled    = true;

    fetch('../backend/dietitian_create_plan.php', { method: 'POST', body: new FormData(this) })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      msgDiv.textContent      = data.message;
      msgDiv.style.background = data.success ? '#dcfce7' : '#fee2e2';
      msgDiv.style.color      = data.success ? '#166534' : '#991b1b';
      msgDiv.style.border     = data.success ? '1px solid #86efac' : '1px solid #fca5a5';
      msgDiv.classList.remove('hidden');

      btn.textContent = 'Save Plan';
      btn.disabled    = false;

      if (data.success) {
        document.getElementById('existing-plan-notice').classList.remove('hidden');
        window.scrollTo(0, 0);
      }
    })
    .catch(function() {
      msgDiv.textContent      = 'Error saving plan. Please try again later.';
      msgDiv.style.background = '#fee2e2';
      msgDiv.style.color      = '#991b1b';
      msgDiv.classList.remove('hidden');
      btn.textContent = 'Save Plan';
      btn.disabled    = false;
    });
  });
</script>
</body>
</html>

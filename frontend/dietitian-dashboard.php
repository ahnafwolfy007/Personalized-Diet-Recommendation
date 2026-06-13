<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('dietitian');
$pageTitle = 'DietSync – Dietitian Dashboard';
$navRole   = 'dietitian';
$navActive = 'dashboard';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="max-w-1200">
      <h1 class="text-3xl mb-8">Dietitian Dashboard</h1>

      <!-- ======== PENDING REQUESTS SECTION ======== -->
      <div class="bg-white border rounded-lg mb-8">
        <div class="p-6 border-b">
          <h2 class="text-xl">Pending Patient Requests</h2>
          <p class="text-sm text-gray mt-1">Patients who want to be assigned to you. Accept or Reject their requests.</p>
        </div>
        <div class="overflow-x-auto">
          <table>
            <thead>
              <tr>
                <th>Patient Name</th>
                <th>Age</th>
                <th>Gender</th>
                <th>Requested On</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="requests-table">
              <tr><td colspan="5" class="text-center text-gray">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ======== ASSIGNED PATIENTS TABLE ======== -->
      <div class="bg-white border rounded-lg">
        <div class="p-6 border-b">
          <h2 class="text-xl">Assigned Patients</h2>
          <p class="text-sm text-gray mt-1">Monitor your patients' daily intake and progress</p>
        </div>
        <div class="overflow-x-auto">
          <table>
            <thead>
              <tr>
                <th>Patient Name</th>
                <th>BMI</th>
                <th>Calorie Requirement</th>
                <th>Today's Intake</th>
                <th>Status</th>
                <th class="text-right">Action</th>
              </tr>
            </thead>
            <tbody id="patients-table">
              <tr><td colspan="6" class="text-center text-gray">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>

</div>

<script>
  // ── LOAD PENDING REQUESTS ───────────────────────────
  function loadRequests() {
    fetch('../backend/dietitian_get_requests.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success) {
          window.location.href = 'login.php';
          return;
        }
        var tbody = document.getElementById('requests-table');

        if (data.requests.length === 0) {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray">No pending requests right now.</td></tr>';
          return;
        }

        tbody.innerHTML = data.requests.map(function(req) {
          return '<tr>' +
            '<td>' + escapeHtml(req.patient_name) + '</td>' +
            '<td class="text-gray">' + escapeHtml(req.age || '—') + '</td>' +
            '<td class="text-gray">' + escapeHtml(req.gender || '—') + '</td>' +
            '<td class="text-gray">' + escapeHtml(req.created_at) + '</td>' +
            '<td>' +
              '<a class="btn btn-secondary btn-sm" style="margin-right:6px;" href="profile-view.php?id=' + Number(req.patient_id) + '">View Profile</a>' +
              '<button class="btn btn-primary btn-sm" style="margin-right:6px;" onclick="respondToRequest(' + Number(req.request_id) + ', \'accept\')">Accept</button>' +
              '<button class="btn btn-secondary btn-sm" onclick="respondToRequest(' + Number(req.request_id) + ', \'reject\')">Reject</button>' +
            '</td>' +
            '</tr>';
        }).join('');
      })
      .catch(function() {
        document.getElementById('requests-table').innerHTML =
          '<tr><td colspan="5" class="text-center text-gray">Could not load requests.</td></tr>';
      });
  }

  // ── ACCEPT OR REJECT a patient request ──────────────
  function respondToRequest(requestId, action) {
    var formData = new FormData();
    formData.append('request_id', requestId);
    formData.append('action', action);

    fetch('../backend/dietitian_respond_request.php', { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      showToast(data.message, data.success ? 'success' : 'error');
      if (data.success) {
        loadRequests();
        loadPatients();
      }
    })
    .catch(function() {
      showToast('An error occurred. Please try again.', 'error');
    });
  }

  // ── LOAD ASSIGNED PATIENTS ──────────────────────────
  function loadPatients() {
    fetch('../backend/dietitian_get_patients.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success) return;
        var tbody = document.getElementById('patients-table');

        if (data.patients.length === 0) {
          tbody.innerHTML = '<tr><td colspan="6" class="text-center text-gray">No patients assigned yet. Accept patient requests above.</td></tr>';
          return;
        }

        tbody.innerHTML = data.patients.map(function(p) {
          var statusClass = p.status === 'Over Eating'  ? 'badge-red'
                          : p.status === 'Under Target' ? 'badge-yellow'
                          : 'badge-green';
          return '<tr>' +
            '<td>' + escapeHtml(p.name) + '</td>' +
            '<td class="text-gray">' + escapeHtml(p.bmi || '—') + '</td>' +
            '<td class="text-gray">' + (p.daily_need ? p.daily_need.toLocaleString() + ' kcal' : '—') + '</td>' +
            '<td class="text-gray">' + (p.today_intake ? p.today_intake.toLocaleString() + ' kcal' : '0 kcal') + '</td>' +
            '<td><span class="badge ' + statusClass + '">' + escapeHtml(p.status) + '</span></td>' +
            '<td class="text-right" style="white-space:nowrap;">' +
              '<a class="btn btn-secondary btn-sm" style="margin-right:6px;" href="profile-view.php?id=' + Number(p.user_id) + '">View</a>' +
              '<button class="btn btn-secondary btn-sm js-unassign" data-id="' + Number(p.user_id) + '" data-name="' + escapeHtml(p.name) + '">Unassign</button>' +
            '</td>' +
            '</tr>';
        }).join('');
      });
  }

  // ── UNASSIGN a patient (with required reason) ───────
  document.getElementById('patients-table').addEventListener('click', function(e) {
    var btn = e.target.closest('.js-unassign');
    if (!btn) return;
    var patientId = btn.getAttribute('data-id');
    var patientName = btn.getAttribute('data-name');
    showReasonModal({
      title: 'Unassign ' + patientName,
      label: 'Explain why you are ending this assignment. The reason will be visible to the patient and the admin.',
      confirmText: 'Unassign patient',
      onConfirm: function(reason, done) {
        var fd = new FormData();
        fd.append('patient_id', patientId);
        fd.append('reason', reason);
        fetch('../backend/unassign.php', { method: 'POST', body: fd })
          .then(function(r) { return r.json(); })
          .then(function(data) {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) { done(); loadPatients(); }
          })
          .catch(function() { showToast('Network error. Please try again.', 'error'); });
      }
    });
  });

  loadRequests();
  loadPatients();
  initRemovalNotice();
</script>
</body>
</html>

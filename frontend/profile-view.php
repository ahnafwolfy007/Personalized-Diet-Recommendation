<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard(); // any authenticated user may view a public profile
$pageTitle = 'DietSync – Profile';
$navRole   = $authUser['role'];
$navActive = '';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="max-w-1200">

      <div class="flex items-center gap-3 mb-6">
        <button type="button" id="back-btn" class="btn btn-secondary btn-sm">← Back</button>
        <h1 class="text-3xl">Profile</h1>
      </div>

      <div id="profile-loading" class="card p-8 text-center text-gray">Loading profile…</div>
      <div id="profile-error" class="hidden card p-8 text-center text-gray"></div>

      <div id="profile-card" class="hidden">
        <!-- Header -->
        <div class="card p-6 mb-6">
          <div class="profile-hero">
            <div class="profile-avatar" id="pv-avatar">?</div>
            <div>
              <h2 class="text-2xl" id="pv-name">—</h2>
              <span class="badge" id="pv-role-badge"></span>
              <p class="text-sm text-gray mt-1" id="pv-since"></p>
            </div>
          </div>
        </div>

        <!-- Details -->
        <div class="card p-6">
          <h3 class="text-xl mb-4" id="pv-details-title">Details</h3>
          <div id="pv-details" class="profile-details"></div>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
  document.getElementById('back-btn').addEventListener('click', function () {
    if (document.referrer && new URL(document.referrer).origin === location.origin) {
      history.back();
    } else {
      location.href = 'index.php';
    }
  });

  var params = new URLSearchParams(location.search);
  var id = parseInt(params.get('id'), 10);

  function row(label, value) {
    if (value === null || value === undefined || value === '') return '';
    return '<div class="pv-row"><span class="pv-label">' + escapeHtml(label) + '</span>' +
           '<span class="pv-value">' + escapeHtml(value) + '</span></div>';
  }

  function render(p) {
    document.getElementById('pv-name').textContent = p.name;
    document.getElementById('pv-avatar').textContent = (p.name || '?').charAt(0).toUpperCase();

    var badge = document.getElementById('pv-role-badge');
    if (p.role === 'dietitian') { badge.textContent = 'Dietitian'; badge.className = 'badge badge-blue'; }
    else { badge.textContent = 'Patient'; badge.className = 'badge badge-green'; }

    document.getElementById('pv-since').textContent = 'Member since ' + p.created_at;

    var details = '';
    if (p.email) details += row('Email', p.email);

    if (p.role === 'dietitian') {
      document.getElementById('pv-details-title').textContent = 'Professional details';
      details += row('Works at', p.works_at);
      details += row('Experience', p.experience_years != null ? p.experience_years + ' years' : '');
      details += row('Specialization', p.specialization);
      details += row('Current patients', p.patient_count);
      if (p.bio) details += '<div class="pv-bio">' + escapeHtml(p.bio) + '</div>';
    } else {
      document.getElementById('pv-details-title').textContent = 'Health overview';
      details += row('Age', p.age != null ? p.age + ' years' : '');
      details += row('Gender', p.gender);
      details += row('Height', p.height_cm != null ? p.height_cm + ' cm' : '');
      details += row('Weight', p.weight_kg != null ? p.weight_kg + ' kg' : '');
      details += row('BMI', p.bmi != null ? p.bmi + ' (' + p.bmi_label + ')' : '');
      details += row('Activity level', p.activity_level);
    }

    if (!details) details = '<p class="text-gray">No additional details provided.</p>';
    document.getElementById('pv-details').innerHTML = details;

    document.getElementById('profile-loading').classList.add('hidden');
    document.getElementById('profile-card').classList.remove('hidden');
  }

  function showError(msg) {
    document.getElementById('profile-loading').classList.add('hidden');
    var el = document.getElementById('profile-error');
    el.textContent = msg;
    el.classList.remove('hidden');
  }

  if (!id) {
    showError('No profile selected.');
  } else {
    fetch('../backend/get_public_profile.php?id=' + encodeURIComponent(id))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) render(data.profile);
        else showError(data.message || 'Profile not found.');
      })
      .catch(function () { showError('Could not load this profile.'); });
  }
</script>
</body>
</html>

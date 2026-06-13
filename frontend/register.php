<?php
require_once __DIR__ . '/../backend/session.php';
// Already authenticated? Skip the registration form.
if (!empty($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? '';
    $home = $role === 'dietitian' ? 'dietitian-dashboard.php'
          : ($role === 'admin' ? 'admin-dashboard.php' : 'user-dashboard.php');
    header('Location: ' . $home);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DietSync – Register</title>
  <link rel="stylesheet" href="./styles/base.css">
  <script src="./assets/app.js"></script>
</head>
<body class="auth-page">

  <div class="auth-wrap-wide">
    <div class="card p-8">

      <!-- Logo -->
      <div class="mb-8">
        <div class="flex items-center gap-2 justify-center mb-6">
          <a href="index.php" class="logo-icon logo-large"></a>
        </div>
        <h1 class="text-2xl text-center mb-2">Create your account</h1>
        <p class="text-center text-gray" id="step-subtitle">First, tell us how you'll use DietSync</p>
      </div>

      <!-- Error/Success message -->
      <div id="error-msg" class="hidden mb-4 p-3 bg-red-light border border-red rounded text-red text-sm"></div>

      <!-- ───────────── STEP 1: ROLE CHOICE ───────────── -->
      <div id="step-role">
        <div class="role-choice">
          <button type="button" class="role-card role-card-patient" data-role="patient">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span class="role-card-title">I'm a Patient</span>
            <span class="role-card-desc">Track meals &amp; calories, get a personalized diet plan from a dietitian.</span>
          </button>
          <button type="button" class="role-card role-card-dietitian" data-role="dietitian">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><circle cx="19" cy="11" r="2"/></svg>
            <span class="role-card-title">I'm a Dietitian</span>
            <span class="role-card-desc">Manage patients, review their intake and build meal plans.</span>
          </button>
        </div>
        <p class="text-center text-gray text-sm mt-6">
          Already have an account? <a href="login.php" class="text-green link-clean">Login</a>
        </p>
      </div>

      <!-- ───────────── STEP 2: DETAILS FORM ───────────── -->
      <form id="register-form" class="hidden">
        <input type="hidden" id="role" name="role" value="patient">

        <div class="role-pill mb-6">
          <span id="role-pill-label">Patient</span>
          <button type="button" id="change-role" class="btn-link">Change</button>
        </div>

        <!-- Shared fields -->
        <div class="grid grid-cols-2 gap-6 mb-6">
          <div>
            <label for="name">Name</label>
            <input type="text" id="name" name="name" placeholder="John Doe" required>
          </div>
          <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="user@example.com" required>
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-with-action">
            <input type="password" id="password" name="password" placeholder="••••••••" required>
            <button type="button" class="pw-toggle" data-target="password" aria-label="Show password" aria-pressed="false">
              <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
            </button>
          </div>
          <p class="text-xs text-gray mt-1">At least 6 characters.</p>
        </div>

        <!-- ─── PATIENT-ONLY fields (health & diet) ─── -->
        <fieldset id="patient-fields" class="role-fields">
          <legend class="role-fields-legend">Health details</legend>
          <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
              <label for="age">Age</label>
              <input type="number" id="age" name="age" placeholder="25" min="1" max="120">
            </div>
            <div>
              <label for="gender">Gender</label>
              <div class="select-wrapper">
                <select id="gender" name="gender">
                  <option>Male</option>
                  <option>Female</option>
                  <option>Other</option>
                </select>
                <span class="select-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
              <label for="height">Height (cm)</label>
              <input type="number" id="height" name="height" placeholder="170" min="50" max="300">
            </div>
            <div>
              <label for="weight">Weight (kg)</label>
              <input type="number" id="weight" name="weight" placeholder="70" min="10" max="500" step="0.1">
            </div>
          </div>
          <div class="form-group">
            <label for="activity">Activity Level</label>
            <div class="select-wrapper">
              <select id="activity" name="activity_level">
                <option>Sedentary (little or no exercise)</option>
                <option>Lightly Active (1-3 days/week)</option>
                <option>Moderately Active (3-5 days/week)</option>
                <option>Very Active (6-7 days/week)</option>
                <option>Extra Active (physical job + exercise)</option>
              </select>
              <span class="select-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
            </div>
          </div>
        </fieldset>

        <!-- ─── DIETITIAN-ONLY fields (professional) ─── -->
        <fieldset id="dietitian-fields" class="role-fields hidden" disabled>
          <legend class="role-fields-legend">Professional details</legend>
          <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
              <label for="works_at">Works at</label>
              <input type="text" id="works_at" name="works_at" placeholder="City Health Clinic">
            </div>
            <div>
              <label for="experience_years">Experience (years)</label>
              <input type="number" id="experience_years" name="experience_years" placeholder="5" min="0" max="80">
            </div>
          </div>
          <div class="form-group">
            <label for="specialization">Specialization / Skills</label>
            <input type="text" id="specialization" name="specialization" placeholder="Weight management, sports nutrition…">
          </div>
          <div class="form-group">
            <label for="bio">Short Bio</label>
            <textarea id="bio" name="bio" rows="3" placeholder="A sentence or two about your practice and approach."></textarea>
          </div>
        </fieldset>

        <button type="submit" id="register-btn" class="btn btn-primary btn-w-full py-3">Create account</button>

        <p class="text-center text-gray text-sm mt-6">
          Already have an account? <a href="login.php" class="text-green link-clean">Login</a>
        </p>
      </form>

    </div>
  </div>

  <script>
    var stepRole   = document.getElementById('step-role');
    var form       = document.getElementById('register-form');
    var subtitle   = document.getElementById('step-subtitle');
    var roleInput  = document.getElementById('role');
    var pillLabel  = document.getElementById('role-pill-label');
    var patientFs  = document.getElementById('patient-fields');
    var dietitianFs = document.getElementById('dietitian-fields');

    function selectRole(role) {
      roleInput.value = role;
      var isPatient = role === 'patient';
      pillLabel.textContent = isPatient ? 'Patient' : 'Dietitian';

      // Toggle visibility AND disabled so hidden fields aren't submitted.
      patientFs.classList.toggle('hidden', !isPatient);
      patientFs.disabled = !isPatient;
      dietitianFs.classList.toggle('hidden', isPatient);
      dietitianFs.disabled = isPatient;

      stepRole.classList.add('hidden');
      form.classList.remove('hidden');
      subtitle.textContent = isPatient
        ? 'Set up your patient profile'
        : 'Set up your dietitian profile';
    }

    document.querySelectorAll('.role-card').forEach(function (btn) {
      btn.addEventListener('click', function () { selectRole(btn.getAttribute('data-role')); });
    });

    document.getElementById('change-role').addEventListener('click', function () {
      form.classList.add('hidden');
      stepRole.classList.remove('hidden');
      subtitle.textContent = "First, tell us how you'll use DietSync";
      document.getElementById('error-msg').classList.add('hidden');
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn    = document.getElementById('register-btn');
      var errDiv = document.getElementById('error-msg');
      errDiv.classList.add('hidden');
      btn.textContent = 'Creating account…';
      btn.disabled = true;

      fetch('../backend/register.php', { method: 'POST', body: new FormData(this) })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.success) {
            window.location.href = data.redirect;
          } else {
            errDiv.textContent = data.message;
            errDiv.classList.remove('hidden');
            btn.textContent = 'Create account';
            btn.disabled = false;
          }
        })
        .catch(function () {
          errDiv.textContent = 'Connection error. Please try again.';
          errDiv.classList.remove('hidden');
          btn.textContent = 'Create account';
          btn.disabled = false;
        });
    });
  </script>

</body>
</html>

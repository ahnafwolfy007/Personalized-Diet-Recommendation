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
</head>
<body class="auth-page">

  <div class="auth-wrap-wide">
    <div class="card p-8">

      <!-- Logo -->
      <div class="mb-8">
        <div class="flex items-center gap-2 justify-center mb-6">
          <a href="index.php" class="logo-icon logo-large"></a>
        </div>
        <h1 class="text-2xl text-center mb-2">Register</h1>
        <p class="text-center text-gray">Create your account and start your health journey</p>
      </div>

      <!-- Error/Success message -->
      <div id="error-msg" class="hidden mb-4 p-3 bg-red-light border border-red rounded text-red text-sm"></div>

      <!-- Form -->
      <form id="register-form">

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
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

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
              <span class="select-arrow">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 12 15 18 9"/>
                </svg>
              </span>
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
            <span class="select-arrow">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </span>
          </div>
        </div>

        <div class="form-group">
          <label for="register-as">Register As</label>
          <div class="select-wrapper">
            <select id="register-as" name="role">
              <option value="patient">Patient</option>
              <option value="dietitian">Dietitian</option>
            </select>
            <span class="select-arrow">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </span>
          </div>
        </div>

        <button type="submit" id="register-btn" class="btn btn-primary btn-w-full py-3">Register</button>
      </form>

      <!-- Login link -->
      <div class="mt-6 text-center">
        <p class="text-gray">
          Already have an account?
          <a href="login.php" class="text-green link-clean">Login</a>
        </p>
      </div>

    </div>
  </div>

  <script>
    document.getElementById('register-form').addEventListener('submit', function(e) {
      e.preventDefault();

      var btn    = document.getElementById('register-btn');
      var errDiv = document.getElementById('error-msg');
      errDiv.classList.add('hidden');
      btn.textContent = 'Registering...';
      btn.disabled = true;

      var formData = new FormData(this);

      fetch('../backend/register.php', {
        method: 'POST',
        body: formData
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success) {
          window.location.href = data.redirect;
        } else {
          errDiv.textContent = data.message;
          errDiv.classList.remove('hidden');
          btn.textContent = 'Register';
          btn.disabled = false;
        }
      })
      .catch(function() {
        errDiv.textContent = 'Connection error. Make sure the server is running.';
        errDiv.classList.remove('hidden');
        btn.textContent = 'Register';
        btn.disabled = false;
      });
    });
  </script>

</body>
</html>

<?php
require_once __DIR__ . '/../backend/session.php';
// Already authenticated? Skip the login form.
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
  <title>DietSync – Login</title>
  <link rel="stylesheet" href="./styles/base.css">
</head>
<body class="auth-page">

  <div class="auth-wrap">
    <div class="card p-8">

      <!-- Logo -->
      <div class="mb-8">
        <div class="flex items-center gap-2 justify-center mb-6">
          <a href="index.php" class="logo-icon logo-large"></a>
        </div>
        <h1 class="text-2xl text-center mb-2">Login</h1>
        <p class="text-center text-gray">Enter your credentials to continue</p>
      </div>

      <!-- Error message area -->
      <div id="error-msg" class="hidden mb-4 p-3 bg-red-light border border-red rounded text-red text-sm"></div>

      <!-- Form -->
      <form id="login-form">
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="user@example.com" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <div class="form-group">
          <label for="login-as">Login As</label>
          <div class="select-wrapper">
            <select id="login-as" name="role">
              <option value="patient">Patient</option>
              <option value="dietitian">Dietitian</option>
              <option value="admin">Admin</option>
            </select>
            <span class="select-arrow">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </span>
          </div>
        </div>

        <button type="submit" id="login-btn" class="btn btn-primary btn-w-full py-3">Login</button>
      </form>

      <!-- Register link -->
      <div class="mt-6 text-center">
        <p class="text-gray">
          Don't have an account?
          <a href="register.php" class="text-green link-clean">Register</a>
        </p>
      </div>

    </div>
  </div>

  <script>
    // Login form submission
    document.getElementById('login-form').addEventListener('submit', function(e) {
      e.preventDefault();

      var btn     = document.getElementById('login-btn');
      var errDiv  = document.getElementById('error-msg');
      errDiv.classList.add('hidden');
      btn.textContent = 'Logging in...';
      btn.disabled = true;

      var formData = new FormData(this);

      fetch('../backend/login.php', {
        method: 'POST',
        body: formData
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success) {
          // Redirect to the appropriate dashboard
          window.location.href = data.redirect;
        } else {
          errDiv.textContent = data.message;
          errDiv.classList.remove('hidden');
          btn.textContent = 'Login';
          btn.disabled = false;
        }
      })
      .catch(function() {
        errDiv.textContent = 'Connection error. Make sure the server is running.';
        errDiv.classList.remove('hidden');
        btn.textContent = 'Login';
        btn.disabled = false;
      });
    });
  </script>

</body>
</html>

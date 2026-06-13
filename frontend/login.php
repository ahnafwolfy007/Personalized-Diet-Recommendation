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
  <script src="./assets/app.js"></script>
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
          <div class="input-with-action">
            <input type="password" id="password" name="password" placeholder="••••••••" required>
            <button type="button" class="pw-toggle" data-target="password" aria-label="Show password" aria-pressed="false">
              <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
            </button>
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

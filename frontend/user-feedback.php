<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('patient');
$pageTitle = 'DietSync – Feedback';
$navRole   = 'patient';
$navActive = 'feedback';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="max-w-1200">
      <h1 class="text-3xl mb-2">Feedback</h1>
      <p class="text-gray mb-8">Send questions or feedback to your dietitian and see their replies.</p>

      <!-- No dietitian state -->
      <div id="no-dietitian" class="hidden card p-8 text-center text-gray">
        <p class="text-xl mb-2">No dietitian assigned yet.</p>
        <p>Choose a dietitian from your <a href="user-profile.php" class="text-green link-clean">Profile</a> page to start a conversation.</p>
      </div>

      <!-- Compose box -->
      <div id="compose" class="hidden card p-6 mb-8">
        <h2 class="text-xl mb-4">New Message</h2>
        <form id="feedback-form">
          <div class="form-group">
            <textarea id="message" name="message" rows="4" maxlength="2000"
              placeholder="Ask a question or share how your plan is going…" required></textarea>
          </div>
          <button type="submit" id="send-btn" class="btn btn-primary flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Send
          </button>
        </form>
      </div>

      <!-- Thread -->
      <div id="feedback-list">
        <p class="text-center text-gray">Loading…</p>
      </div>
      <div class="mt-6" id="feedback-pagination"></div>

    </div>
  </main>

</div>

<script>
  var feedbackPage = 1;

  function loadFeedback(page) {
    feedbackPage = page || 1;
    fetch('../backend/patient_get_feedback.php?page=' + feedbackPage)
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success) { window.location.href = 'login.php'; return; }

        var list = document.getElementById('feedback-list');
        renderPagination(document.getElementById('feedback-pagination'), data.pagination, loadFeedback);

        if (!data.has_dietitian) {
          document.getElementById('no-dietitian').classList.remove('hidden');
          document.getElementById('compose').classList.add('hidden');
          list.innerHTML = '';
          document.getElementById('feedback-pagination').innerHTML = '';
          return;
        }

        document.getElementById('no-dietitian').classList.add('hidden');
        document.getElementById('compose').classList.remove('hidden');

        if (data.feedbacks.length === 0) {
          list.innerHTML = '<div class="card p-8 text-center text-gray"><p>No messages yet. Send your first message above.</p></div>';
          return;
        }

        list.innerHTML = data.feedbacks.map(function(fb) {
          var statusBadge = fb.status === 'responded'
            ? '<span class="badge badge-green">Answered</span>'
            : '<span class="badge badge-yellow">Awaiting reply</span>';

          var responseSection = '';
          if (fb.status === 'responded' && fb.response) {
            responseSection = '<div class="response-box">' +
              '<p class="text-sm text-gray mb-1">Reply from ' + escapeHtml(fb.dietitian_name) + ':</p>' +
              '<p class="text-gray" style="white-space:pre-wrap;">' + escapeHtml(fb.response) + '</p>' +
              '</div>';
          }

          return '<div class="card p-6 mb-4">' +
            '<div class="flex items-start justify-between mb-4">' +
              '<div>' +
                '<h3 class="card-name">You</h3>' +
                '<p class="text-sm text-gray">' + escapeHtml(fb.created_at) + '</p>' +
              '</div>' +
              statusBadge +
            '</div>' +
            '<div class="note-box mb-4"><p class="text-gray" style="white-space:pre-wrap;">' + escapeHtml(fb.message) + '</p></div>' +
            responseSection +
            '</div>';
        }).join('');
      })
      .catch(function() {
        document.getElementById('feedback-list').innerHTML =
          '<div class="card p-8 text-center text-gray"><p>Could not load feedback. Please try again later.</p></div>';
      });
  }

  document.getElementById('feedback-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn   = document.getElementById('send-btn');
    var field = document.getElementById('message');

    if (!field.value.trim()) { showToast('Please write a message first.', 'error'); return; }

    btn.disabled = true;

    fetch('../backend/patient_send_feedback.php', { method: 'POST', body: new FormData(this) })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        showToast(data.message, data.success ? 'success' : 'error');
        btn.disabled = false;
        if (data.success) {
          field.value = '';
          loadFeedback(1); // newest message is on page 1
        }
      })
      .catch(function() {
        btn.disabled = false;
        showToast('Could not send. Please try again later.', 'error');
      });
  });

  loadFeedback(1);
</script>
</body>
</html>

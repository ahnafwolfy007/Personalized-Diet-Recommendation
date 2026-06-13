<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('dietitian');
$pageTitle = 'DietSync – Patient Feedback';
$navRole   = 'dietitian';
$navActive = 'feedback';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="max-w-1200">
      <h1 class="text-3xl mb-8">Patient Feedback</h1>

      <div id="feedback-list">
        <p class="text-center text-gray">Loading feedback…</p>
      </div>
      <div class="mt-6" id="feedback-pagination"></div>

    </div>
  </main>

</div>

<script>
  var feedbackPage = 1;

  function loadFeedback(page) {
    feedbackPage = page || 1;
    fetch('../backend/dietitian_get_feedback.php?page=' + feedbackPage)
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success) { window.location.href = 'login.php'; return; }
        var container = document.getElementById('feedback-list');
        renderPagination(document.getElementById('feedback-pagination'), data.pagination, loadFeedback);

        if (data.feedbacks.length === 0) {
          container.innerHTML = '<div class="card p-8 text-center text-gray"><p>No feedback messages yet.</p></div>';
          return;
        }

        container.innerHTML = data.feedbacks.map(function(fb) {
          var statusBadge = fb.status === 'responded'
            ? '<span class="badge badge-green">Responded</span>'
            : '<span class="badge badge-yellow">Pending</span>';

          var responseSection;
          if (fb.status === 'responded') {
            responseSection = '<div class="response-box">' +
              '<p class="text-sm text-gray mb-1">Your Response:</p>' +
              '<p class="text-gray">' + escapeHtml(fb.response) + '</p>' +
              '</div>';
          } else {
            responseSection = '<div>' +
              '<textarea rows="3" placeholder="Write your response…" class="response-input" id="resp-' + Number(fb.feedback_id) + '"></textarea>' +
              '<button class="btn btn-primary btn-sm flex items-center gap-2" onclick="sendResponse(' + Number(fb.feedback_id) + ')">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>' +
                'Send Response' +
              '</button>' +
              '</div>';
          }

          return '<div class="card p-6 mb-4">' +
            '<div class="flex items-start justify-between mb-4">' +
              '<div class="flex items-start gap-4">' +
                '<div class="icon-box-lg">' +
                  '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>' +
                '</div>' +
                '<div>' +
                  '<h3 class="card-name">' + escapeHtml(fb.patient_name) + '</h3>' +
                  '<p class="text-sm text-gray">' + escapeHtml(fb.created_at) + '</p>' +
                '</div>' +
              '</div>' +
              statusBadge +
            '</div>' +
            '<div class="note-box mb-4"><p class="text-gray" style="white-space:pre-wrap;">' + escapeHtml(fb.message) + '</p></div>' +
            responseSection +
            '</div>';
        }).join('');
      });
  }

  function sendResponse(feedbackId) {
    var textarea = document.getElementById('resp-' + feedbackId);
    var response = textarea ? textarea.value.trim() : '';

    if (!response) {
      showToast('Please write a response before sending.', 'error');
      return;
    }

    var formData = new FormData();
    formData.append('feedback_id', feedbackId);
    formData.append('response', response);

    fetch('../backend/dietitian_send_feedback.php', { method: 'POST', body: formData })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) loadFeedback(feedbackPage);
      })
      .catch(function() { showToast('Network error. Please try again.', 'error'); });
  }

  loadFeedback(1);
</script>
</body>
</html>

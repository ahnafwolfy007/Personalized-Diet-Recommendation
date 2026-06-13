<?php
require_once __DIR__ . '/guard.php';
$authUser  = guard('patient');
$pageTitle = 'DietSync – Diet Plan';
$navRole   = 'patient';
$navActive = 'diet-plan';
include __DIR__ . '/partials/head.php';
?>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="max-w-1200">
      <h1 class="text-3xl mb-2">Your Diet Plan</h1>
      <p class="text-gray mb-8" id="dietitian-name">Loading…</p>

      <!-- No plan notice -->
      <div id="no-plan" class="hidden card p-8 text-center text-gray">
        <p class="text-xl mb-2">No diet plan assigned yet.</p>
        <p>A dietitian will create your personalized meal plan soon.</p>
      </div>

      <!-- Plan content -->
      <div id="plan-content" class="hidden">
        <!-- Breakfast -->
        <div class="card p-6 mb-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="icon-box">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg>
            </div>
            <div>
              <h2 class="text-xl">Breakfast</h2>
              <p class="text-sm text-gray">Morning meal</p>
            </div>
          </div>
          <div class="note-box">
            <p id="breakfast-text" class="text-gray text-relaxed" style="white-space:pre-wrap;"></p>
          </div>
        </div>

        <!-- Lunch -->
        <div class="card p-6 mb-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="icon-box">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
            </div>
            <div>
              <h2 class="text-xl">Lunch</h2>
              <p class="text-sm text-gray">Midday meal</p>
            </div>
          </div>
          <div class="note-box">
            <p id="lunch-text" class="text-gray text-relaxed" style="white-space:pre-wrap;"></p>
          </div>
        </div>

        <!-- Dinner -->
        <div class="card p-6 mb-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="icon-box">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
            </div>
            <div>
              <h2 class="text-xl">Dinner</h2>
              <p class="text-sm text-gray">Evening meal</p>
            </div>
          </div>
          <div class="note-box">
            <p id="dinner-text" class="text-gray text-relaxed" style="white-space:pre-wrap;"></p>
          </div>
        </div>

        <!-- Dietitian Notes -->
        <div class="card p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="icon-box">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
            </div>
            <h2 class="text-xl">Dietitian Notes</h2>
          </div>
          <div class="note-box">
            <p id="plan-notes" class="text-gray text-relaxed" style="white-space:pre-wrap;"></p>
          </div>
        </div>
      </div>

    </div>
  </main>

</div>

<script>
  fetch('../backend/get_diet_plan.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) {
        var plan = data.plan;
        document.getElementById('dietitian-name').textContent =
          'Created by Dietitian: ' + plan.dietitian_name;
        document.getElementById('breakfast-text').textContent = plan.breakfast_text || 'Not specified.';
        document.getElementById('lunch-text').textContent     = plan.lunch_text     || 'Not specified.';
        document.getElementById('dinner-text').textContent    = plan.dinner_text    || 'Not specified.';
        document.getElementById('plan-notes').textContent     = plan.notes          || 'No notes.';
        document.getElementById('plan-content').classList.remove('hidden');
      } else {
        document.getElementById('dietitian-name').textContent = '';
        document.getElementById('no-plan').classList.remove('hidden');
      }
    })
    .catch(function() {
      window.location.href = 'login.php';
    });
</script>
</body>
</html>

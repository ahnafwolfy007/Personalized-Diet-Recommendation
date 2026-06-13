<?php
// frontend/partials/sidebar.php
// Shared, role-aware sidebar. Before including, the page sets:
//   $navRole   = 'patient' | 'dietitian' | 'admin'
//   $navActive = key of the current page (e.g. 'dashboard')

$icons = [
    'dashboard' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
    'log-food'  => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><line x1="12" y1="11" x2="16" y2="11"/><line x1="12" y1="16" x2="16" y2="16"/><line x1="8" y1="11" x2="8.01" y2="11"/><line x1="8" y1="16" x2="8.01" y2="16"/>',
    'report'    => '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
    'diet-plan' => '<line x1="3" y1="2" x2="3" y2="12"/><path d="M3 12a2 2 0 0 0 4 0V6"/><line x1="5" y1="2" x2="5" y2="6"/><line x1="7" y1="2" x2="7" y2="6"/><line x1="21" y1="2" x2="21" y2="22"/><path d="M17 2a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2v8"/>',
    'meal-log'  => '<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
    'profile'   => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'feedback'  => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    'users'     => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'water'     => '<path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>',
    'analytics' => '<path d="M3 3v18h18"/><rect x="7" y="10" width="3" height="7"/><rect x="12" y="6" width="3" height="11"/><rect x="17" y="13" width="3" height="4"/>',
    'foods'     => '<path d="M3 2v7c0 1.1.9 2 2 2h0a2 2 0 0 0 2-2V2"/><path d="M5 2v20"/><path d="M16 2v20"/><path d="M16 8c0-3 1.5-5 3-5v17"/>',
    'activity'  => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
    'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
];

$menus = [
    'patient' => [
        ['key' => 'dashboard', 'href' => 'user-dashboard.php', 'label' => 'Dashboard',    'icon' => 'dashboard'],
        ['key' => 'log-food',  'href' => 'user-log-food.php',  'label' => 'Log Food',     'icon' => 'log-food'],
        ['key' => 'report',    'href' => 'user-report.php',    'label' => 'Daily Report', 'icon' => 'report'],
        ['key' => 'diet-plan', 'href' => 'user-diet-plan.php', 'label' => 'Diet Plan',    'icon' => 'diet-plan'],
        ['key' => 'meal-log',  'href' => 'user-meal-log.php',  'label' => 'Meal Log',     'icon' => 'meal-log'],
        ['key' => 'water',     'href' => 'user-water.php',     'label' => 'Water Intake', 'icon' => 'water'],
        ['key' => 'feedback',  'href' => 'user-feedback.php',  'label' => 'Feedback',     'icon' => 'feedback'],
        ['key' => 'profile',   'href' => 'user-profile.php',   'label' => 'Profile',      'icon' => 'profile'],
    ],
    'dietitian' => [
        ['key' => 'dashboard',   'href' => 'dietitian-dashboard.php',   'label' => 'Dashboard',         'icon' => 'dashboard'],
        ['key' => 'create-plan', 'href' => 'dietitian-create-plan.php', 'label' => 'Meal Plans',        'icon' => 'diet-plan'],
        ['key' => 'analytics',   'href' => 'dietitian-analytics.php',   'label' => 'Patient Analytics', 'icon' => 'analytics'],
        ['key' => 'feedback',    'href' => 'dietitian-feedback.php',    'label' => 'Feedback',          'icon' => 'feedback'],
        ['key' => 'profile',     'href' => 'dietitian-profile.php',     'label' => 'Profile',           'icon' => 'profile'],
    ],
    'admin' => [
        ['key' => 'dashboard', 'href' => 'admin-dashboard.php', 'label' => 'Dashboard',        'icon' => 'dashboard'],
        ['key' => 'users',     'href' => 'admin-users.php',     'label' => 'User Management',  'icon' => 'users'],
        ['key' => 'foods',     'href' => 'admin-foods.php',     'label' => 'Foods',            'icon' => 'foods'],
        ['key' => 'analytics', 'href' => 'admin-analytics.php', 'label' => 'Analytics',        'icon' => 'analytics'],
        ['key' => 'activity',  'href' => 'admin-activity.php',  'label' => 'Activity Monitor', 'icon' => 'activity'],
    ],
];

$items = $menus[$navRole ?? 'patient'] ?? [];

/** Render an inline nav SVG from a path string. */
function nav_svg(string $paths): string
{
    return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" '
        . 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" '
        . 'stroke-linejoin="round">' . $paths . '</svg>';
}
?>
<button id="sidebar-toggle" class="sidebar-toggle" aria-label="Toggle menu" aria-controls="sidebar" aria-expanded="false">
  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
</button>
<div id="sidebar-overlay" class="sidebar-overlay"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <a href="index.php" class="logo-icon"></a>
      <span class="logo-text">DietSync</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <?php foreach ($items as $it): ?>
      <a href="<?= htmlspecialchars($it['href']) ?>" class="nav-link<?= ($it['key'] === ($navActive ?? '')) ? ' active' : '' ?>">
        <?= nav_svg($icons[$it['icon']]) ?>
        <?= htmlspecialchars($it['label']) ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer">
    <a href="../backend/logout.php" class="nav-link logout">
      <?= nav_svg($icons['logout']) ?>
      Logout
    </a>
  </div>
</aside>

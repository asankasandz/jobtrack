<?php
// Sidebar shared by every HR and Super Admin page.
// The page that includes this file sets $activePage first, e.g. 'vacancies.manage'.
$isAdmin = Auth::isAdmin();
$pendingBadge = $isAdmin ? User::countPending() : 0;   // registrations waiting for approval

// Small line icons (inline SVG, so no image files or icon library are needed).
$svgStart = '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">';
$icons = [
    'dashboard' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
    'users'     => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
    'file'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
    'chart'     => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
];
?>
<div class="hr-sidebar">
  <div class="side-brand">
    <span class="logo">J</span>
    <div>JobTrack<small><?= $isAdmin ? 'Super Admin' : 'HR Portal' ?></small></div>
  </div>

  <div class="side-label">Main</div>
  <?php if ($isAdmin): ?>
    <a class="hr-nav-item <?= $activePage === 'admin.dashboard' ? 'active' : '' ?>" href="index.php?route=admin.dashboard"><?= $svgStart . $icons['dashboard'] ?></svg>Dashboard</a>
  <?php else: ?>
    <a class="hr-nav-item <?= $activePage === 'hr.dashboard' ? 'active' : '' ?>" href="index.php?route=hr.dashboard"><?= $svgStart . $icons['dashboard'] ?></svg>Dashboard</a>
  <?php endif; ?>
  <a class="hr-nav-item <?= $activePage === 'vacancies.manage' ? 'active' : '' ?>" href="index.php?route=vacancies.manage"><?= $svgStart . $icons['briefcase'] ?></svg>Vacancies</a>
  <?php if ($isAdmin): ?>
    <a class="hr-nav-item <?= $activePage === 'hr.dashboard' ? 'active' : '' ?>" href="index.php?route=hr.dashboard"><?= $svgStart . $icons['file'] ?></svg>Applications</a>

    <div class="side-label">Administration</div>
    <a class="hr-nav-item <?= $activePage === 'admin.users' ? 'active' : '' ?>" href="index.php?route=admin.users"><?= $svgStart . $icons['users'] ?></svg>Users<?php if ($pendingBadge > 0): ?><span class="nav-badge"><?= $pendingBadge ?></span><?php endif; ?></a>
    <a class="hr-nav-item <?= $activePage === 'admin.reports' ? 'active' : '' ?>" href="index.php?route=admin.reports"><?= $svgStart . $icons['chart'] ?></svg>Reports</a>
  <?php endif; ?>
</div>

<?php
// Sidebar shared by every HR and Super Admin page.
// The page that includes this file sets $activePage first, e.g. 'vacancies.manage'.
$isAdmin = Auth::isAdmin();
?>
<div class="hr-sidebar">
  <div class="side-brand">JobTrack <span><?= $isAdmin ? 'Super Admin' : 'HR' ?></span></div>

  <?php if ($isAdmin): ?>
    <a class="hr-nav-item <?= $activePage === 'admin.dashboard' ? 'active' : '' ?>" href="index.php?route=admin.dashboard">Dashboard</a>
    <a class="hr-nav-item <?= $activePage === 'admin.users' ? 'active' : '' ?>" href="index.php?route=admin.users">Users</a>
  <?php else: ?>
    <a class="hr-nav-item <?= $activePage === 'hr.dashboard' ? 'active' : '' ?>" href="index.php?route=hr.dashboard">Dashboard</a>
  <?php endif; ?>

  <a class="hr-nav-item <?= $activePage === 'vacancies.manage' ? 'active' : '' ?>" href="index.php?route=vacancies.manage">Vacancies</a>

  <?php if ($isAdmin): ?>
    <a class="hr-nav-item <?= $activePage === 'hr.dashboard' ? 'active' : '' ?>" href="index.php?route=hr.dashboard">Applications</a>
    <a class="hr-nav-item <?= $activePage === 'admin.reports' ? 'active' : '' ?>" href="index.php?route=admin.reports">Reports</a>
  <?php endif; ?>
</div>

<?php
// White top bar shared by every HR and Super Admin page:
// menu button (small screens), then the logged-in user and the Log out button.
$barName = $_SESSION['user_name'];
$barInitial = strtoupper(substr($barName, 0, 1));
$barRole = Auth::isAdmin() ? 'Super Admin' : 'HR';
?>
<div class="topbar-staff">
  <button type="button" class="menu-btn" aria-label="Menu" onclick="document.body.classList.toggle('nav-open')">
    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>
  <div class="user-chip">
    <span class="avatar-circle"><?= htmlspecialchars($barInitial) ?></span>
    <div><b><?= htmlspecialchars($barName) ?></b><small><?= $barRole ?></small></div>
    <a class="logout-link" href="index.php?route=logout">
      <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Log out
    </a>
  </div>
</div>

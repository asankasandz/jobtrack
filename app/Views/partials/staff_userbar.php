<div class="userbar">
  <?= htmlspecialchars($_SESSION['user_name']) ?> (<?= Auth::isAdmin() ? 'Super Admin' : 'HR' ?>)
  &nbsp;|&nbsp; <a href="index.php?route=logout">Log out</a>
</div>

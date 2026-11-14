<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Dashboard</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="topbar">
  <div class="brand">JobTrack</div>
  <div class="user">
    <div class="avatar"></div><?= htmlspecialchars($_SESSION['user_name']) ?> (Applicant)
    &nbsp;|&nbsp;<a href="index.php?route=home#vacancies">Browse Vacancies</a>
    &nbsp;|&nbsp;<a href="index.php?route=applications.mine">My Applications</a>
    &nbsp;|&nbsp;<a href="index.php?route=logout">Log out</a>
  </div>
</div>
<div class="content">
  <div class="h1">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?></div>

  <div class="stat-row">
    <div class="stat-card"><div class="stat-num"><?= (int) $stats['open_vacancies'] ?></div><div class="stat-label">OPEN VACANCIES</div></div>
    <div class="stat-card"><div class="stat-num"><?= (int) $stats['my_applications'] ?></div><div class="stat-label">MY APPLICATIONS</div></div>
    <div class="stat-card"><div class="stat-num"><?= (int) $stats['active'] ?></div><div class="stat-label">IN PROGRESS</div></div>
    <div class="stat-card"><div class="stat-num"><?= (int) $stats['awaiting_decision'] ?></div><div class="stat-label">AWAITING DECISION</div></div>
    <div class="stat-card"><div class="stat-num"><?= (int) $stats['offers'] ?></div><div class="stat-label">OFFERS</div></div>
  </div>

  <div style="display:flex;gap:14px;margin-bottom:24px;flex-wrap:wrap;">
    <a href="index.php?route=home#vacancies"><button class="btn-sm">Browse Open Vacancies</button></a>
    <a href="index.php?route=applications.mine"><button class="btn-sm neutral">View All My Applications</button></a>
  </div>

  <div class="h1" style="font-size:16px;">Recent Applications</div>
  <?php if (empty($recent)): ?>
    <p style="color:#8894a8;">You haven't applied to any vacancies yet.</p>
  <?php endif; ?>
  <?php foreach ($recent as $app): ?>
    <div class="app-row">
      <div>
        <b><?= htmlspecialchars($app['vacancy_title']) ?></b>
        <div style="font-size:12px;color:#8894a8;"><?= htmlspecialchars($app['department']) ?></div>
      </div>
      <span class="badge b-<?= strtolower($app['status']) ?>"><?= htmlspecialchars(Report::label($app['status'])) ?></span>
    </div>
  <?php endforeach; ?>
</div>
</body>
</html>

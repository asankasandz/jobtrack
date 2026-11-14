<?php
$stageOrder = ['Applied', 'Screening', 'InterviewScheduled', 'InterviewCompleted', 'DecisionPending'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Applications</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="topbar">
  <div class="brand">JobTrack</div>
  <div class="user">
    <div class="avatar"></div><?= htmlspecialchars($_SESSION['user_name']) ?> (Applicant)
    &nbsp;|&nbsp;<a href="index.php?route=applicant.dashboard">Dashboard</a>
    &nbsp;|&nbsp;<a href="index.php?route=home">Open Vacancies</a>
    &nbsp;|&nbsp;<a href="index.php?route=logout">Log out</a>
  </div>
</div>
<div class="content">
  <div class="h1">My Applications</div>
  <?php if (empty($applications)): ?>
    <p>You have not submitted any applications yet.</p>
  <?php endif; ?>
  <?php foreach ($applications as $app): ?>
    <div class="card">
      <div class="app-row" style="border-bottom:none;">
        <div>
          <b><?= htmlspecialchars($app['vacancy_title']) ?></b>
          <div style="font-size:12px;color:#8894a8;"><?= htmlspecialchars($app['department']) ?></div>
        </div>
        <span class="badge b-<?= strtolower($app['status']) ?>"><?= htmlspecialchars($app['status']) ?></span>
      </div>

      <?php
      // The newest note written by HR (history is oldest first, so keep the last one found).
      $latestNote = '';
      foreach ($app['history'] as $h) {
          if (!empty($h['note']) && $h['from_status'] !== null) {
              $latestNote = $h['note'];
          }
      }
      ?>
      <?php if ($latestNote !== ''): ?>
        <div class="latest-note"><b>Latest update from HR:</b><br><?= nl2br(htmlspecialchars($latestNote)) ?></div>
      <?php endif; ?>

      <?php if (in_array($app['status'], $stageOrder, true)): ?>
        <div class="timeline">
          <?php foreach ($stageOrder as $i => $stage):
              $reached = array_search($app['status'], $stageOrder, true) >= $i; ?>
            <div class="tdot <?= $reached ? '' : 'pending' ?>"></div>
            <?php if ($i < count($stageOrder) - 1): ?><div class="tline"></div><?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <details style="margin-top:10px;">
        <summary style="cursor:pointer;font-size:12px;color:#35547f;">View status history</summary>
        <ul style="margin-top:8px;font-size:12px;color:#444;">
          <?php foreach ($app['history'] as $h): ?>
            <li><?= htmlspecialchars($h['from_status'] ?? 'start') ?> &rarr; <?= htmlspecialchars($h['to_status']) ?>
              (<?= htmlspecialchars($h['changed_at']) ?>)
              <?php if (!empty($h['note'])): ?>
                <div class="hist-note">Note: <?= nl2br(htmlspecialchars($h['note'])) ?></div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </details>

      <?php if (!in_array($app['status'], ['Offered', 'Rejected', 'Withdrawn'], true)): ?>
        <form method="post" action="index.php?route=applications.withdraw" style="margin-top:10px;"
              onsubmit="return confirm('Withdraw this application?');">
          <?= Auth::csrfField() ?>
          <input type="hidden" name="id" value="<?= (int) $app['id'] ?>">
          <button type="submit" class="btn-sm danger">Withdraw</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
</body>
</html>

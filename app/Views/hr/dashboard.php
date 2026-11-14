<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= Auth::isAdmin() ? 'Applications' : 'HR Dashboard' ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="hr-wrap">
  <?php $activePage = 'hr.dashboard'; require __DIR__ . '/../partials/staff_sidebar.php'; ?>
  <div class="hr-main">
    <?php require __DIR__ . '/../partials/staff_userbar.php'; ?>
    <div class="h1"><?= Auth::isAdmin() ? 'Applications Pipeline' : 'HR Dashboard &amp; Pipeline' ?></div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error"><?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <div class="stat-row">
      <div class="stat-card"><div class="stat-num"><?= (int) $stats['open_vacancies'] ?></div><div class="stat-label">OPEN VACANCIES</div></div>
      <div class="stat-card"><div class="stat-num"><?= (int) $stats['total_applications'] ?></div><div class="stat-label">TOTAL APPLICATIONS</div></div>
      <div class="stat-card"><div class="stat-num"><?= (int) $stats['in_screening'] ?></div><div class="stat-label">IN SCREENING</div></div>
      <div class="stat-card"><div class="stat-num"><?= (int) $stats['interview_stage'] ?></div><div class="stat-label">INTERVIEW STAGE</div></div>
      <div class="stat-card"><div class="stat-num"><?= (int) $stats['offers_made'] ?></div><div class="stat-label">OFFERS MADE</div></div>
    </div>

    <div class="hr-table">
      <div class="hr-row" style="font-weight:700;color:#8894a8;">
        <div>Applicant</div><div>Vacancy</div><div>Stage</div><div>CV</div><div style="flex:1.6;">Actions</div>
      </div>
      <?php foreach ($applications as $app):
          $next = Application::nextStatuses($app['status']); ?>
        <div class="hr-row">
          <div><?= htmlspecialchars($app['applicant_name']) ?></div>
          <div><?= htmlspecialchars($app['vacancy_title']) ?></div>
          <div><span class="badge b-<?= strtolower($app['status']) ?>"><?= htmlspecialchars(Report::label($app['status'])) ?></span></div>
          <div><a href="index.php?route=applications.resume&id=<?= (int) $app['id'] ?>">Download</a></div>
          <div class="hr-actions" style="flex:1.6;">
            <?php if (!empty($next)): ?>
              <form method="post" action="index.php?route=applications.advance" style="display:flex;gap:4px;align-items:center;">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="id" value="<?= (int) $app['id'] ?>">
                <select name="new_status" style="height:28px;font-size:11px;">
                  <?php foreach ($next as $n): ?>
                    <option value="<?= htmlspecialchars($n) ?>"><?= htmlspecialchars(Report::label($n)) ?></option>
                  <?php endforeach; ?>
                </select>
                <textarea name="note" class="note-box" rows="3" placeholder="Note for the applicant (e.g. interview date, time, online link or venue)"></textarea>
                <button type="submit" class="act-btn act-advance">Go</button>
              </form>
            <?php else: ?>
              <span style="font-size:11px;color:#8894a8;">Final status</span>
            <?php endif; ?>
          </div>
        </div>
        <details class="hr-history">
          <summary>History &amp; notes (<?= count($app['history']) ?>)</summary>
          <ul>
            <?php foreach ($app['history'] as $h): ?>
              <li><?= htmlspecialchars(Report::stageText($h['from_status'], $h['to_status'])) ?>
                by <?= htmlspecialchars($h['changed_by_name']) ?> (<?= htmlspecialchars($h['changed_at']) ?>)
                <?php if (!empty($h['note'])): ?>
                  <div class="hist-note">Note: <?= nl2br(htmlspecialchars($h['note'])) ?></div>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</div>
</body>
</html>

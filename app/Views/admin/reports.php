<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reports</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="hr-wrap">
  <?php $activePage = 'admin.reports'; require __DIR__ . '/../partials/staff_sidebar.php'; ?>
  <div class="hr-main">
    <?php require __DIR__ . '/../partials/staff_userbar.php'; ?>

    <div class="page-head page-head-row">
      <div>
        <div class="h1">Reports</div>
        <p class="page-sub">Every stage of every application: who passed or failed, with HR's note.</p>
      </div>
      <a class="btn-primary" href="index.php?route=admin.reports.csv&amp;<?= htmlspecialchars($csvQuery) ?>">&#8681; Download CSV</a>
    </div>

    <!-- Summary of the rows below -->
    <div class="cards4">
      <div class="stat-card"><div class="stat-num"><?= $summary['Applied'] ?></div><div class="stat-label">APPLICATIONS SUBMITTED</div></div>
      <div class="stat-card"><div class="stat-num" style="color:#2e9e5b;"><?= $summary['Passed'] ?></div><div class="stat-label">STAGES PASSED</div></div>
      <div class="stat-card"><div class="stat-num" style="color:#c0392b;"><?= $summary['Failed'] ?></div><div class="stat-label">FAILED (REJECTED)</div></div>
      <div class="stat-card"><div class="stat-num" style="color:#8894a8;"><?= $summary['Withdrawn'] ?></div><div class="stat-label">WITHDRAWN</div></div>
    </div>

    <!-- Filters -->
    <form method="get" action="index.php" class="panel filters">
      <input type="hidden" name="route" value="admin.reports">
      <div>
        <label class="field-label">Vacancy</label>
        <select name="vacancy_id">
          <option value="0">All vacancies</option>
          <?php foreach ($vacancies as $v): ?>
            <option value="<?= (int) $v['id'] ?>" <?= $filters['vacancy_id'] == $v['id'] ? 'selected' : '' ?>><?= htmlspecialchars($v['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="field-label">Result</label>
        <select name="result">
          <option value="">All results</option>
          <option value="passed"    <?= $filters['result'] === 'passed' ? 'selected' : '' ?>>Passed</option>
          <option value="failed"    <?= $filters['result'] === 'failed' ? 'selected' : '' ?>>Failed (rejected)</option>
          <option value="withdrawn" <?= $filters['result'] === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
        </select>
      </div>
      <div>
        <label class="field-label">From</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>">
      </div>
      <div>
        <label class="field-label">To</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>">
      </div>
      <div class="filter-buttons">
        <button type="submit" class="btn-primary">Filter</button>
        <a class="btn-cancel" href="index.php?route=admin.reports">Reset</a>
      </div>
    </form>

    <div class="panel">
      <?php if (empty($rows)): ?>
        <p class="muted">No records match these filters.</p>
      <?php else: ?>
        <?php $tableRows = $rows; require __DIR__ . '/../partials/activity_table.php'; ?>
        <p class="help-note"><?= count($rows) ?> record(s). &ldquo;Passed&rdquo; means the applicant passed the stage they moved on from.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>

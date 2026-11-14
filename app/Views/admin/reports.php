<?php $badge = ['Applied' => 'b-applied', 'Passed' => 'b-offered', 'Failed' => 'b-rejected', 'Withdrawn' => 'b-withdrawn']; ?>
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
    <div class="h1">Stage-by-stage Report</div>

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
        <button type="submit" class="btn-sm">Filter</button>
        <a href="index.php?route=admin.reports"><button type="button" class="btn-sm neutral">Reset</button></a>
        <a href="index.php?route=admin.reports.csv&amp;<?= htmlspecialchars($csvQuery) ?>"><button type="button" class="btn-sm neutral">Download CSV</button></a>
      </div>
    </form>

    <!-- Summary of the rows below -->
    <div class="stat-row">
      <div class="stat-card"><div class="stat-num"><?= $summary['Applied'] ?></div><div class="stat-label">APPLICATIONS SUBMITTED</div></div>
      <div class="stat-card"><div class="stat-num" style="color:#2e9e5b;"><?= $summary['Passed'] ?></div><div class="stat-label">STAGES PASSED</div></div>
      <div class="stat-card"><div class="stat-num" style="color:#c0392b;"><?= $summary['Failed'] ?></div><div class="stat-label">FAILED (REJECTED)</div></div>
      <div class="stat-card"><div class="stat-num" style="color:#8894a8;"><?= $summary['Withdrawn'] ?></div><div class="stat-label">WITHDRAWN</div></div>
    </div>

    <div class="panel">
      <?php if (empty($rows)): ?>
        <p class="muted">No records match these filters.</p>
      <?php else: ?>
        <div class="tbl-wrap">
          <table class="tbl">
            <thead>
              <tr><th>Date</th><th>Applicant</th><th>Email</th><th>Vacancy</th><th>Stage</th><th>Result</th><th>Note</th><th>Decided by</th></tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): $result = Report::outcome($row['from_status'], $row['to_status']); ?>
                <tr>
                  <td><?= htmlspecialchars($row['changed_at']) ?></td>
                  <td><b><?= htmlspecialchars($row['applicant_name']) ?></b></td>
                  <td><?= htmlspecialchars($row['applicant_email']) ?></td>
                  <td><?= htmlspecialchars($row['vacancy_title']) ?></td>
                  <td><?= htmlspecialchars($row['from_status'] ?? 'Application') ?> &rarr; <?= htmlspecialchars($row['to_status']) ?></td>
                  <td><span class="badge <?= $badge[$result] ?>"><?= $result ?></span></td>
                  <td class="note-cell"><?= nl2br(htmlspecialchars((string) $row['note'])) ?></td>
                  <td><?= htmlspecialchars($row['changed_by_name']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p class="muted" style="margin-top:10px;"><?= count($rows) ?> record(s). &ldquo;Passed&rdquo; means the applicant passed the stage they moved on from.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>

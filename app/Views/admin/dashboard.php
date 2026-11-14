<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Super Admin Dashboard</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="hr-wrap">
  <?php $activePage = 'admin.dashboard'; require __DIR__ . '/../partials/staff_sidebar.php'; ?>
  <div class="hr-main">
    <?php require __DIR__ . '/../partials/staff_userbar.php'; ?>
    <div class="h1">Dashboard</div>

    <!-- Summary cards -->
    <div class="stat-row">
      <div class="stat-card big">
        <div class="stat-icon">&#128101;</div>
        <div class="stat-num"><?= (int) $stats['total_users'] ?></div>
        <div class="stat-title">Users</div>
        <div class="stat-sub"><?= (int) $stats['hr_staff'] ?> HR &middot; <?= (int) $stats['applicants'] ?> applicants &middot; <?= (int) $stats['inactive_users'] ?> inactive</div>
      </div>
      <div class="stat-card big">
        <div class="stat-icon">&#128188;</div>
        <div class="stat-num"><?= (int) $stats['total_vacancies'] ?></div>
        <div class="stat-title">Vacancies</div>
        <div class="stat-sub"><?= (int) $stats['open_vacancies'] ?> currently open</div>
      </div>
      <div class="stat-card big">
        <div class="stat-icon">&#128196;</div>
        <div class="stat-num"><?= (int) $stats['total_applications'] ?></div>
        <div class="stat-title">Applications</div>
        <div class="stat-sub">All time</div>
      </div>
      <div class="stat-card big">
        <div class="stat-icon">&#127942;</div>
        <div class="stat-num"><?= (int) $stats['offers'] ?></div>
        <div class="stat-title">Offers made</div>
        <div class="stat-sub"><?= (int) $stats['rejected'] ?> rejected</div>
      </div>
    </div>

    <!-- Charts -->
    <div class="grid-2">
      <div class="panel">
        <div class="panel-head"><h2>Applications</h2><span>Last 30 days</span></div>
        <div class="chart-wrap"><canvas id="perDayChart"></canvas></div>
      </div>
      <div class="panel">
        <div class="panel-head"><h2>Applications by stage</h2><span>Right now</span></div>
        <div class="chart-wrap"><canvas id="statusChart"></canvas></div>
      </div>
    </div>

    <!-- Latest activity -->
    <div class="panel">
      <div class="panel-head">
        <h2>Latest activity</h2>
        <a href="index.php?route=admin.reports">Full report &rarr;</a>
      </div>
      <?php if (empty($recent)): ?>
        <p class="muted">Nothing has happened yet.</p>
      <?php else: ?>
        <?php $badge = ['Applied' => 'b-applied', 'Passed' => 'b-offered', 'Failed' => 'b-rejected', 'Withdrawn' => 'b-withdrawn']; ?>
        <div class="tbl-wrap">
          <table class="tbl">
            <thead>
              <tr><th>Date</th><th>Applicant</th><th>Vacancy</th><th>Stage</th><th>Result</th><th>Note</th><th>By</th></tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $row): $result = Report::outcome($row['from_status'], $row['to_status']); ?>
                <tr>
                  <td><?= htmlspecialchars($row['changed_at']) ?></td>
                  <td><?= htmlspecialchars($row['applicant_name']) ?></td>
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
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Chart.js (charts library), loaded from a CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// Line chart: applications received per day (like the sample dashboard)
new Chart(document.getElementById('perDayChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($perDay['labels']) ?>,
    datasets: [{
      label: 'Applications',
      data: <?= json_encode($perDay['values']) ?>,
      borderColor: '#3b6fd4',
      backgroundColor: 'rgba(59, 111, 212, 0.15)',
      fill: true,
      tension: 0.3,
      pointBackgroundColor: '#fff',
      pointBorderColor: '#3b6fd4',
      pointBorderWidth: 2
    }]
  },
  options: {
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { maxTicksLimit: 8 } } }
  }
});

// Bar chart: how many applications are in each stage
new Chart(document.getElementById('statusChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($byStatus['labels']) ?>,
    datasets: [{
      label: 'Applications',
      data: <?= json_encode($byStatus['values']) ?>,
      backgroundColor: ['#4a7fd6', '#c98a1f', '#7b52c9', '#7b52c9', '#8894a8', '#2e9e5b', '#c0392b', '#8894a8']
    }]
  },
  options: {
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { autoSkip: false, maxRotation: 60 } } }
  }
});
</script>
</body>
</html>

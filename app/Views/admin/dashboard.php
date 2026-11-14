<?php
// Readable stage names for the bar chart ("InterviewScheduled" -> "Interview Scheduled").
$stageLabels = [];
foreach ($byStatus['labels'] as $stage) {
    $stageLabels[] = Report::label($stage);
}
?>
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

    <div class="page-head">
      <div class="h1">Dashboard</div>
      <p class="page-sub">Overview of users, vacancies and applications.</p>
    </div>

    <?php if ($stats['pending_users'] > 0): ?>
      <a class="pending-banner" href="index.php?route=admin.users&role=pending">
        <b><?= (int) $stats['pending_users'] ?> registration<?= $stats['pending_users'] == 1 ? '' : 's' ?></b> waiting for your approval &rarr;
      </a>
    <?php endif; ?>

    <!-- Summary cards -->
    <div class="cards4">
      <div class="stat-card big">
        <div class="stat-icon ic-blue">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-num"><?= (int) $stats['total_users'] ?></div>
        <div class="stat-title">Users</div>
        <div class="stat-sub"><?= (int) $stats['hr_staff'] ?> HR &middot; <?= (int) $stats['applicants'] ?> employees &middot; <?= (int) $stats['inactive_users'] ?> inactive</div>
      </div>
      <div class="stat-card big">
        <div class="stat-icon ic-purple">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        </div>
        <div class="stat-num"><?= (int) $stats['total_vacancies'] ?></div>
        <div class="stat-title">Vacancies</div>
        <div class="stat-sub"><?= (int) $stats['open_vacancies'] ?> currently open</div>
      </div>
      <div class="stat-card big">
        <div class="stat-icon ic-teal">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <div class="stat-num"><?= (int) $stats['total_applications'] ?></div>
        <div class="stat-title">Applications</div>
        <div class="stat-sub">All time</div>
      </div>
      <div class="stat-card big">
        <div class="stat-icon ic-green">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
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
        <?php $tableRows = $recent; require __DIR__ . '/../partials/activity_table.php'; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Chart.js (charts library), loaded from a CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Segoe UI', Arial, sans-serif";
Chart.defaults.color = '#6b778c';

// Line chart: applications received per day (like the sample dashboard)
new Chart(document.getElementById('perDayChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($perDay['labels']) ?>,
    datasets: [{
      label: 'Applications',
      data: <?= json_encode($perDay['values']) ?>,
      borderColor: '#3b6fd4',
      backgroundColor: 'rgba(59, 111, 212, 0.12)',
      fill: true,
      tension: 0.35,
      pointRadius: 3,
      pointHoverRadius: 6,
      pointBackgroundColor: '#fff',
      pointBorderColor: '#3b6fd4',
      pointBorderWidth: 2
    }]
  },
  options: {
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef0f5' } },
      x: { ticks: { maxTicksLimit: 8 }, grid: { display: false } }
    }
  }
});

// Horizontal bar chart: how many applications are in each stage
new Chart(document.getElementById('statusChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($stageLabels) ?>,
    datasets: [{
      label: 'Applications',
      data: <?= json_encode($byStatus['values']) ?>,
      borderRadius: 6,
      barThickness: 16,
      backgroundColor: ['#4a7fd6', '#c98a1f', '#7b52c9', '#7b52c9', '#8894a8', '#2e9e5b', '#c0392b', '#b3bccb']
    }]
  },
  options: {
    indexAxis: 'y',
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef0f5' } },
      y: { grid: { display: false } }
    }
  }
});
</script>
</body>
</html>

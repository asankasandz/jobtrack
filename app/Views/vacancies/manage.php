<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Vacancies</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="hr-wrap">
  <?php $activePage = 'vacancies.manage'; require __DIR__ . '/../partials/staff_sidebar.php'; ?>
  <div class="hr-main">
    <?php require __DIR__ . '/../partials/staff_userbar.php'; ?>
    <div class="h1">Manage Vacancies</div>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error"><?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <p style="margin-bottom:14px;"><a href="index.php?route=vacancies.create"><button class="btn-sm">+ New Vacancy</button></a></p>
    <?php foreach ($vacancies as $v): ?>
      <div class="vac-card">
        <div style="display:flex;gap:12px;align-items:center;">
          <?php if (!empty($v['image_path'])): ?>
            <img src="assets/uploads/vacancies/<?= htmlspecialchars($v['image_path']) ?>" alt=""
                 style="width:56px;height:56px;object-fit:cover;border-radius:6px;">
          <?php else: ?>
            <div style="width:56px;height:56px;border-radius:6px;background:#e2e6ef;"></div>
          <?php endif; ?>
          <div>
          <div class="vac-title"><?= htmlspecialchars($v['title']) ?>
            <span class="badge <?= $v['status'] === 'open' ? 'b-applied' : 'b-withdrawn' ?>"><?= htmlspecialchars($v['status']) ?></span>
          </div>
          <div class="vac-dept"><?= htmlspecialchars($v['department']) ?></div>
          </div>
        </div>
        <div class="hr-actions">
          <a href="index.php?route=vacancies.edit&id=<?= (int) $v['id'] ?>"><button class="btn-sm neutral">Edit</button></a>
          <form method="post" action="index.php?route=vacancies.delete" onsubmit="return confirm('Delete this vacancy?');">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
            <button type="submit" class="btn-sm danger">Delete</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>

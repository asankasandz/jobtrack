<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Apply: <?= htmlspecialchars($vacancy['title']) ?></title>
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
  <div class="h1">Apply: <?= htmlspecialchars($vacancy['title']) ?></div>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
  <?php else: ?>
    <div class="card">
      <form method="post" action="index.php?route=applications.store" enctype="multipart/form-data">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="vacancy_id" value="<?= (int) $vacancy['id'] ?>">
        <label class="form-label">Cover Letter</label>
        <textarea name="cover_letter" placeholder="Briefly explain why you're a good fit..."></textarea>
        <label class="form-label">Upload CV (PDF, DOC, or DOCX, max 2MB)</label>
        <div class="dropzone">
          <input type="file" name="resume" accept=".pdf,.doc,.docx" required>
        </div>
        <button type="submit" class="btn">Submit Application</button>
      </form>
    </div>
  <?php endif; ?>
</div>
</body>
</html>

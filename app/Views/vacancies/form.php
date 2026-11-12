<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $vacancy ? 'Edit Vacancy' : 'New Vacancy' ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="hr-wrap">
  <div class="hr-sidebar">
    <a class="hr-nav-item" href="index.php?route=hr.dashboard">Dashboard</a>
    <a class="hr-nav-item active" href="index.php?route=vacancies.manage">Vacancies</a>
    <a class="hr-nav-item" href="index.php?route=hr.dashboard">Applications</a>
  </div>
  <div class="hr-main">
    <div class="h1"><?= $vacancy ? 'Edit Vacancy' : 'New Vacancy' ?></div>
    <div class="card" style="max-width:600px;">
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error"><?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
      <?php endif; ?>
      <form method="post" action="index.php?route=<?= $vacancy ? 'vacancies.update' : 'vacancies.store' ?>" enctype="multipart/form-data">
        <?php if ($vacancy): ?><input type="hidden" name="id" value="<?= (int) $vacancy['id'] ?>"><?php endif; ?>
        <div class="field">
          <label class="field-label">Title</label>
          <input type="text" name="title" value="<?= htmlspecialchars($vacancy['title'] ?? $old['title'] ?? '') ?>" required>
        </div>
        <div class="field">
          <label class="field-label">Department</label>
          <input type="text" name="department" value="<?= htmlspecialchars($vacancy['department'] ?? $old['department'] ?? '') ?>" required>
        </div>
        <div class="field">
          <label class="field-label">Description</label>
          <textarea name="description" required><?= htmlspecialchars($vacancy['description'] ?? $old['description'] ?? '') ?></textarea>
        </div>
        <div class="field">
          <label class="field-label">Requirements</label>
          <textarea name="requirements"><?= htmlspecialchars($vacancy['requirements'] ?? $old['requirements'] ?? '') ?></textarea>
        </div>
        <div class="field">
          <label class="field-label">Vacancy Image (JPG, PNG, or WEBP, max 2MB)</label>
          <?php if (!empty($vacancy['image_path'])): ?>
            <img src="assets/uploads/vacancies/<?= htmlspecialchars($vacancy['image_path']) ?>"
                 alt="Current vacancy image" style="width:140px;border-radius:6px;display:block;margin-bottom:8px;">
            <div style="font-size:11px;color:#8894a8;margin-bottom:6px;">Current image shown above. Choose a new file only if you want to replace it.</div>
          <?php endif; ?>
          <div class="dropzone">
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
          </div>
        </div>
        <?php if ($vacancy): ?>
        <div class="field">
          <label class="field-label">Status</label>
          <select name="status">
            <option value="open" <?= $vacancy['status'] === 'open' ? 'selected' : '' ?>>Open</option>
            <option value="closed" <?= $vacancy['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
          </select>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn"><?= $vacancy ? 'Save Changes' : 'Create Vacancy' ?></button>
      </form>
    </div>
  </div>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Internal Job Application Tracking System</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-title">Internal Job Application<br>Tracking System</div>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <form method="post" action="index.php?route=login&next=<?= urlencode($_GET['next'] ?? '') ?>">
      <?= Auth::csrfField() ?>
      <div class="field">
        <label class="field-label">Email</label>
        <input type="email" name="email" required>
      </div>
      <div class="field">
        <label class="field-label">Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn">Log In</button>
    </form>
    <div class="btn-link">No account? Ask HR or your administrator to create one for you.</div>
  </div>
</div>
</body>
</html>

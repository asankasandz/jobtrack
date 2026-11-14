<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Registration - Internal Job Application Tracking System</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-title">Employee Registration</div>
    <p class="muted" style="text-align:center;margin:-10px 0 18px 0;">
      This is an internal system. After you register, the Super Admin approves your account and gives you an Employee ID. You log in with that ID and your password.
    </p>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <form method="post" action="index.php?route=signup">
      <?= Auth::csrfField() ?>
      <div class="field">
        <label class="field-label">Full Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label class="field-label">Work Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label class="field-label">Password (min. 8 characters)</label>
        <input type="password" name="password" required>
      </div>
      <div class="field">
        <label class="field-label">Confirm Password</label>
        <input type="password" name="password_confirm" required>
      </div>
      <button type="submit" class="btn">Register</button>
    </form>
    <div class="btn-link">Already approved? <a href="index.php?route=login"><b>Log in</b></a></div>
  </div>
</div>
</body>
</html>

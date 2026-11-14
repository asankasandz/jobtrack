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
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <form method="post" action="index.php?route=login&next=<?= urlencode($_GET['next'] ?? '') ?>">
      <?= Auth::csrfField() ?>
      <div class="field">
        <label class="field-label">Employee ID</label>
        <input type="text" name="employee_id" autocomplete="username" maxlength="30" required>
      </div>
      <div class="field">
        <label class="field-label">Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn">Log In</button>
    </form>
    <div class="muted" style="text-align:center;margin-top:14px;">Your Employee ID is given to you by the Super Admin when your registration is approved.</div>
    <div class="btn-link">New employee? <a href="index.php?route=signup"><b>Register here</b></a></div>
  </div>
</div>
</body>
</html>

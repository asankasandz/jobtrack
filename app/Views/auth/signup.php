<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sign Up - Internal Job Application Tracking System</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-title">Create Applicant Account</div>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <form method="post" action="index.php?route=signup&next=<?= urlencode($_GET['next'] ?? '') ?>">
      <div class="field">
        <label class="field-label">Full Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label class="field-label">Email</label>
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
      <button type="submit" class="btn">Sign Up</button>
    </form>
    <div class="btn-link">Already have an account? <a href="index.php?route=login&next=<?= urlencode($_GET['next'] ?? '') ?>"><b>Log in</b></a></div>
  </div>
</div>
</body>
</html>

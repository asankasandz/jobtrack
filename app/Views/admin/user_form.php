<?php
// Used for both "Add User" ($user is null) and "Edit User" ($user is the row).
$isMe = ($user !== null && $user['id'] == Auth::id());
$current = [
    'name'  => $user['name']  ?? $old['name']  ?? '',
    'email' => $user['email'] ?? $old['email'] ?? '',
    'role'  => $user['role']  ?? $old['role']  ?? 'hr',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $user ? 'Edit User' : 'Add User' ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="hr-wrap">
  <?php $activePage = 'admin.users'; require __DIR__ . '/../partials/staff_sidebar.php'; ?>
  <div class="hr-main">
    <?php require __DIR__ . '/../partials/staff_userbar.php'; ?>
    <div class="page-head">
      <div class="h1"><?= $user ? 'Edit User' : 'Add User' ?></div>
      <p class="page-sub"><?= $user ? 'Change the details of this account. Leave the password empty to keep it unchanged.' : 'Create an HR, Super Admin or applicant account.' ?></p>
    </div>

    <div class="panel form-panel">
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error"><?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
      <?php endif; ?>

      <form method="post" action="index.php?route=<?= $user ? 'admin.users.update' : 'admin.users.store' ?>">
        <?= Auth::csrfField() ?>
        <?php if ($user): ?><input type="hidden" name="id" value="<?= (int) $user['id'] ?>"><?php endif; ?>

        <div class="field">
          <label class="field-label">Full name</label>
          <input type="text" name="name" value="<?= htmlspecialchars($current['name']) ?>" required>
        </div>
        <div class="field">
          <label class="field-label">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($current['email']) ?>" required>
        </div>
        <div class="field">
          <label class="field-label">Role</label>
          <select name="role" <?= $isMe ? 'disabled' : '' ?>>
            <option value="hr"        <?= $current['role'] === 'hr' ? 'selected' : '' ?>>HR staff</option>
            <option value="admin"     <?= $current['role'] === 'admin' ? 'selected' : '' ?>>Super Admin</option>
            <option value="applicant" <?= $current['role'] === 'applicant' ? 'selected' : '' ?>>Applicant</option>
          </select>
          <?php if ($isMe): ?><div class="muted" style="margin-top:4px;">You cannot change your own role.</div><?php endif; ?>
        </div>
        <div class="field">
          <label class="field-label"><?= $user ? 'New password (leave empty to keep the current one)' : 'Password (min 8 characters)' ?></label>
          <input type="password" name="password" autocomplete="new-password" <?= $user ? '' : 'required' ?>>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-primary"><?= $user ? 'Save changes' : 'Create user' ?></button>
          <a href="index.php?route=admin.users" class="btn-cancel">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>

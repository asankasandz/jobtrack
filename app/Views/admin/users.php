<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Users</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="hr-wrap">
  <?php $activePage = 'admin.users'; require __DIR__ . '/../partials/staff_sidebar.php'; ?>
  <div class="hr-main">
    <?php require __DIR__ . '/../partials/staff_userbar.php'; ?>
    <div class="h1">Manage Users</div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error"><?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="toolbar">
      <div class="tabs">
        <a class="<?= $role === '' ? 'active' : '' ?>" href="index.php?route=admin.users">All (<?= array_sum($counts) ?>)</a>
        <a class="<?= $role === 'admin' ? 'active' : '' ?>" href="index.php?route=admin.users&role=admin">Super Admins (<?= $counts['admin'] ?>)</a>
        <a class="<?= $role === 'hr' ? 'active' : '' ?>" href="index.php?route=admin.users&role=hr">HR staff (<?= $counts['hr'] ?>)</a>
        <a class="<?= $role === 'applicant' ? 'active' : '' ?>" href="index.php?route=admin.users&role=applicant">Applicants (<?= $counts['applicant'] ?>)</a>
      </div>
      <a href="index.php?route=admin.users.create"><button class="btn-sm">+ Add User</button></a>
    </div>

    <div class="panel">
      <div class="tbl-wrap">
        <table class="tbl">
          <thead>
            <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): $isMe = ($u['id'] == Auth::id()); ?>
              <tr>
                <td><b><?= htmlspecialchars($u['name']) ?></b><?= $isMe ? ' <span class="muted">(you)</span>' : '' ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="role role-<?= htmlspecialchars($u['role']) ?>"><?= $u['role'] === 'admin' ? 'Super Admin' : ($u['role'] === 'hr' ? 'HR' : 'Applicant') ?></span></td>
                <td><span class="status <?= $u['is_active'] == 1 ? 'status-on' : 'status-off' ?>"><?= $u['is_active'] == 1 ? 'Active' : 'Inactive' ?></span></td>
                <td><?= htmlspecialchars(substr($u['created_at'], 0, 10)) ?></td>
                <td>
                  <div class="hr-actions">
                    <a href="index.php?route=admin.users.edit&id=<?= (int) $u['id'] ?>"><button class="btn-sm neutral">Edit</button></a>
                    <?php if (!$isMe): ?>
                      <form method="post" action="index.php?route=admin.users.toggle">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                        <button type="submit" class="btn-sm neutral"><?= $u['is_active'] == 1 ? 'Deactivate' : 'Activate' ?></button>
                      </form>
                      <form method="post" action="index.php?route=admin.users.delete"
                            onsubmit="return confirm('Delete this user permanently?');">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                        <button type="submit" class="btn-sm danger">Delete</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="muted" style="margin-top:12px;">
        A user who already has vacancies, applications or history cannot be deleted &mdash; deactivate them instead.
        A deactivated user cannot log in, but all their records stay intact.
      </p>
    </div>
  </div>
</div>
</body>
</html>

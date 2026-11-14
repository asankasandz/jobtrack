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

    <div class="page-head page-head-row">
      <div>
        <div class="h1">Users</div>
        <p class="page-sub">Approve new registrations, or add, edit, deactivate and delete accounts for employees, HR staff and administrators.</p>
      </div>
      <a class="btn-primary" href="index.php?route=admin.users.create">+ Add User</a>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error"><?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="panel">
      <div class="toolbar">
        <div class="tabs">
          <a class="<?= $role === '' ? 'active' : '' ?>" href="index.php?route=admin.users">All <span><?= array_sum($counts) ?></span></a>
          <a class="<?= $role === 'admin' ? 'active' : '' ?>" href="index.php?route=admin.users&role=admin">Super Admins <span><?= $counts['admin'] ?></span></a>
          <a class="<?= $role === 'hr' ? 'active' : '' ?>" href="index.php?route=admin.users&role=hr">HR staff <span><?= $counts['hr'] ?></span></a>
          <a class="<?= $role === 'applicant' ? 'active' : '' ?>" href="index.php?route=admin.users&role=applicant">Employees <span><?= $counts['applicant'] ?></span></a>
          <a class="<?= $role === 'pending' ? 'active' : '' ?> <?= $pendingCount > 0 ? 'has-pending' : '' ?>" href="index.php?route=admin.users&role=pending">Pending approval <span><?= $pendingCount ?></span></a>
        </div>
        <input type="search" id="userSearch" class="search" placeholder="Search name or email...">
      </div>

      <div class="tbl-wrap">
        <table class="tbl" id="userTable">
          <thead>
            <tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th><th class="right">Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): $isMe = ($u['id'] == Auth::id()); ?>
              <tr>
                <td>
                  <div class="user-cell">
                    <span class="avatar-circle sm role-bg-<?= htmlspecialchars($u['role']) ?>"><?= htmlspecialchars(strtoupper(substr($u['name'], 0, 1))) ?></span>
                    <div>
                      <b><?= htmlspecialchars($u['name']) ?><?= $isMe ? ' <span class="you">you</span>' : '' ?></b>
                      <small><?= htmlspecialchars($u['email']) ?></small>
                      <?php if (!empty($u['employee_id'])): ?><small>Employee ID: <?= htmlspecialchars($u['employee_id']) ?></small><?php endif; ?>
                    </div>
                  </div>
                </td>
                <td><span class="role role-<?= htmlspecialchars($u['role']) ?>"><?= $u['role'] === 'admin' ? 'Super Admin' : ($u['role'] === 'hr' ? 'HR' : 'Employee') ?></span></td>
                <td>
                  <?php if ($role === 'pending'): ?>
                    <span class="status status-pending">Pending</span>
                  <?php else: ?>
                    <span class="status <?= $u['is_active'] == 1 ? 'status-on' : 'status-off' ?>"><?= $u['is_active'] == 1 ? 'Active' : 'Inactive' ?></span>
                  <?php endif; ?>
                </td>
                <td class="nowrap"><?= date('j M Y', strtotime($u['created_at'])) ?></td>
                <td>
                  <?php if ($role === 'pending'): ?>
                  <div class="row-actions">
                    <form method="post" action="index.php?route=admin.users.approve">
                      <?= Auth::csrfField() ?>
                      <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                      <button type="submit" class="act good">Approve</button>
                    </form>
                    <form method="post" action="index.php?route=admin.users.reject"
                          onsubmit="return confirm('Reject this registration? The pending account will be deleted.');">
                      <?= Auth::csrfField() ?>
                      <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                      <button type="submit" class="act del">Reject</button>
                    </form>
                  </div>
                  <?php else: ?>
                  <div class="row-actions">
                    <a class="act" href="index.php?route=admin.users.edit&id=<?= (int) $u['id'] ?>">Edit</a>
                    <?php if (!$isMe): ?>
                      <form method="post" action="index.php?route=admin.users.toggle">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                        <button type="submit" class="act <?= $u['is_active'] == 1 ? 'warn' : 'good' ?>"><?= $u['is_active'] == 1 ? 'Deactivate' : 'Activate' ?></button>
                      </form>
                      <form method="post" action="index.php?route=admin.users.delete"
                            onsubmit="return confirm('Delete this user permanently?');">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                        <button type="submit" class="act del">Delete</button>
                      </form>
                    <?php endif; ?>
                  </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if (empty($users)): ?>
        <p class="muted" style="padding:14px 4px;"><?= $role === 'pending' ? 'No registrations are waiting for approval.' : 'No users here yet.' ?></p>
      <?php endif; ?>
      <p class="no-match muted" id="noMatch" style="display:none;">No users match your search.</p>

      <?php if ($role === 'pending'): ?>
      <p class="help-note">
        <b>Tip:</b> check the Employee ID against your staff records, then <b>Approve</b> to let the person log in, or <b>Reject</b> to delete the registration.
      </p>
      <?php else: ?>
      <p class="help-note">
        <b>Tip:</b> a user who already has vacancies, applications or history cannot be deleted &mdash; deactivate them instead.
        A deactivated user cannot log in, but all their records stay intact.
      </p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Live search: hide the rows that do not contain the typed text.
document.getElementById('userSearch').addEventListener('input', function () {
  var text = this.value.toLowerCase();
  var rows = document.querySelectorAll('#userTable tbody tr');
  var shown = 0;
  rows.forEach(function (row) {
    var match = row.textContent.toLowerCase().indexOf(text) !== -1;
    row.style.display = match ? '' : 'none';
    if (match) { shown++; }
  });
  document.getElementById('noMatch').style.display = shown === 0 ? '' : 'none';
});
</script>
</body>
</html>

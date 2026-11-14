<?php

// Super Admin only: dashboard, user management (add / edit / deactivate / delete)
// and reports. Everything HR can do (vacancies, applications) is also open to the
// admin through the normal HR pages - see Auth::requireStaff().
class AdminController
{
    // ---------- Dashboard ----------

    public function dashboard()
    {
        Auth::requireRole('admin');

        $stats = Report::dashboardStats();
        $perDay = Report::applicationsPerDay(30);
        $byStatus = Report::applicationsByStatus();
        $recent = Report::stageReport([], 8);   // the 8 latest status changes

        require __DIR__ . '/../Views/admin/dashboard.php';
    }

    // ---------- Users ----------

    // List users, optionally only one role (?role=hr).
    public function users()
    {
        Auth::requireRole('admin');

        $role = $_GET['role'] ?? '';
        if (!in_array($role, ['admin', 'hr', 'applicant'])) {
            $role = '';
        }

        $users = User::all($role);
        $counts = User::countByRole();

        $errors = $_SESSION['errors'] ?? [];
        $success = $_SESSION['success'] ?? '';
        unset($_SESSION['errors'], $_SESSION['success']);

        require __DIR__ . '/../Views/admin/users.php';
    }

    public function showCreate()
    {
        Auth::requireRole('admin');

        $user = null;
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        require __DIR__ . '/../Views/admin/user_form.php';
    }

    public function store()
    {
        Auth::requireRole('admin');

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        $errors = $this->validateUser($name, $email, $password, $role, null, true);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = ['name' => $name, 'email' => $email, 'role' => $role];
            header('Location: index.php?route=admin.users.create');
            exit;
        }

        User::create($name, $email, $password, $role);
        $_SESSION['success'] = 'User "' . $name . '" was created.';
        header('Location: index.php?route=admin.users');
        exit;
    }

    public function showEdit()
    {
        Auth::requireRole('admin');

        $user = User::findById((int) ($_GET['id'] ?? 0));
        if ($user === null) {
            http_response_code(404);
            echo '<h1>404</h1><p>User not found.</p>';
            return;
        }

        $errors = $_SESSION['errors'] ?? [];
        $old = [];
        unset($_SESSION['errors']);

        require __DIR__ . '/../Views/admin/user_form.php';
    }

    public function update()
    {
        Auth::requireRole('admin');

        $id = (int) ($_POST['id'] ?? 0);
        $user = User::findById($id);
        if ($user === null) {
            http_response_code(404);
            echo '<h1>404</h1><p>User not found.</p>';
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';   // empty = keep the current password
        $role = $_POST['role'] ?? '';

        // The admin cannot change their own role (they could lock themselves out).
        if ($id == Auth::id()) {
            $role = $user['role'];
        }

        $errors = $this->validateUser($name, $email, $password, $role, $id, false);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: index.php?route=admin.users.edit&id=' . $id);
            exit;
        }

        User::update($id, $name, $email, $role, $password);
        $_SESSION['success'] = 'User "' . $name . '" was updated.';
        header('Location: index.php?route=admin.users');
        exit;
    }

    // Deactivate an active user, or re-activate an inactive one.
    public function toggle()
    {
        Auth::requireRole('admin');

        $id = (int) ($_POST['id'] ?? 0);
        $user = User::findById($id);

        if ($user === null) {
            $_SESSION['errors'] = ['User not found.'];
        } elseif ($id == Auth::id()) {
            $_SESSION['errors'] = ['You cannot deactivate your own account.'];
        } elseif ($user['is_active'] == 1) {
            User::setActive($id, 0);
            $_SESSION['success'] = '"' . $user['name'] . '" was deactivated and can no longer log in.';
        } else {
            User::setActive($id, 1);
            $_SESSION['success'] = '"' . $user['name'] . '" was re-activated.';
        }

        header('Location: index.php?route=admin.users');
        exit;
    }

    // Delete a user - only if nothing else in the database refers to them.
    public function destroy()
    {
        Auth::requireRole('admin');

        $id = (int) ($_POST['id'] ?? 0);
        $user = User::findById($id);

        if ($user === null) {
            $_SESSION['errors'] = ['User not found.'];
        } elseif ($id == Auth::id()) {
            $_SESSION['errors'] = ['You cannot delete your own account.'];
        } elseif (User::hasRecords($id)) {
            $_SESSION['errors'] = ['"' . $user['name'] . '" has vacancies, applications or history records, so they cannot be deleted. Deactivate the account instead.'];
        } else {
            User::delete($id);
            $_SESSION['success'] = 'User "' . $user['name'] . '" was deleted.';
        }

        header('Location: index.php?route=admin.users');
        exit;
    }

    // ---------- Reports ----------

    // Stage-by-stage report: who passed or failed each stage, with HR's note.
    public function reports()
    {
        Auth::requireRole('admin');

        $filters = $this->reportFilters();
        $rows = Report::stageReport($filters);
        $vacancies = Vacancy::allForHr();

        // Count the results for the summary cards.
        $summary = ['Applied' => 0, 'Passed' => 0, 'Failed' => 0, 'Withdrawn' => 0];
        foreach ($rows as $row) {
            $summary[Report::outcome($row['from_status'], $row['to_status'])]++;
        }

        // The same filters again, as a query string for the "Download CSV" link.
        $csvQuery = http_build_query(array_filter($filters));

        require __DIR__ . '/../Views/admin/reports.php';
    }

    // Same report as a downloadable CSV file (opens in Excel).
    public function reportsCsv()
    {
        Auth::requireRole('admin');

        $rows = Report::stageReport($this->reportFilters());

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="stage-report-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");   // lets Excel read UTF-8 correctly
        fputcsv($out, ['Date', 'Applicant', 'Email', 'Vacancy', 'From stage', 'To stage', 'Result', 'Note', 'Decided by']);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row['changed_at'],
                $this->csvSafe($row['applicant_name']),
                $this->csvSafe($row['applicant_email']),
                $this->csvSafe($row['vacancy_title']),
                $row['from_status'] === null ? 'Application' : $row['from_status'],
                $row['to_status'],
                Report::outcome($row['from_status'], $row['to_status']),
                $this->csvSafe($row['note']),
                $this->csvSafe($row['changed_by_name']),
            ]);
        }

        fclose($out);
        exit;
    }

    // ---------- Helper functions ----------

    // Read and clean the report filters from the URL.
    private function reportFilters()
    {
        $filters = [];

        $filters['vacancy_id'] = (int) ($_GET['vacancy_id'] ?? 0);

        $filters['result'] = $_GET['result'] ?? '';
        if (!in_array($filters['result'], ['passed', 'failed', 'withdrawn'])) {
            $filters['result'] = '';
        }

        // Dates must look like 2026-11-14, anything else is ignored.
        foreach (['date_from', 'date_to'] as $key) {
            $value = $_GET[$key] ?? '';
            $filters[$key] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
        }

        return $filters;
    }

    // In Excel, a cell that starts with = + - or @ is run as a formula. Names and
    // notes come from users, so put a ' in front to make them plain text.
    private function csvSafe($value)
    {
        $value = (string) $value;
        if ($value !== '' && strpos('=+-@', $value[0]) !== false) {
            return "'" . $value;
        }
        return $value;
    }

    // Returns a list of error messages (empty list = everything is fine).
    // $userId is the user being edited (null when creating a new one).
    private function validateUser($name, $email, $password, $role, $userId, $passwordRequired)
    {
        $errors = [];

        if ($name === '' || $email === '') {
            $errors[] = 'Name and email are required.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (!in_array($role, ['applicant', 'hr', 'admin'])) {
            $errors[] = 'Please choose a role.';
        }
        if ($passwordRequired && strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (!$passwordRequired && $password !== '' && strlen($password) < 8) {
            $errors[] = 'The new password must be at least 8 characters (or leave it empty to keep the current one).';
        }

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if ($userId === null && User::emailExists($email)) {
                $errors[] = 'An account with this email already exists.';
            }
            if ($userId !== null && User::emailTakenByOther($email, $userId)) {
                $errors[] = 'Another account already uses this email.';
            }
        }

        return $errors;
    }
}

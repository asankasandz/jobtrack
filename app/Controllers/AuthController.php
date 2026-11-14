<?php

// Login, registration and logout.
// This is an INTERNAL system, so registering does not give access: a new account is
// "pending" (is_approved = 0) and cannot log in until the Super Admin approves it.
class AuthController
{
    public function showLogin()
    {
        // Already logged in? Go straight to the dashboard.
        if (Auth::check()) {
            header('Location: index.php?route=' . $this->dashboardFor(Auth::role()));
            exit;
        }
        $errors = $_SESSION['errors'] ?? [];
        $success = $_SESSION['success'] ?? '';
        unset($_SESSION['errors'], $_SESSION['success']);
        require __DIR__ . '/../Views/auth/login.php';
    }

    public function login()
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors = [];
        $user = null;

        if ($email === '' || $password === '') {
            $errors[] = 'Please enter both email and password.';
        } else {
            $user = User::findByEmail($email);
            // Same message for "no such email" and "wrong password",
            // so attackers can't find out which emails are registered.
            if ($user === null || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'Incorrect email or password.';
            } elseif ($user['is_approved'] != 1) {
                // Registered, but the Super Admin has not approved the account yet.
                $errors[] = 'Your registration is waiting for approval by the Super Admin. You can log in once it has been approved.';
            } elseif ($user['is_active'] != 1) {
                // Deactivated by the Super Admin (password was correct, account is switched off).
                $errors[] = 'This account has been deactivated. Please contact the administrator.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: index.php?route=login');
            exit;
        }

        Auth::login($user);
        header('Location: ' . $this->pageAfterLogin($user['role']));
        exit;
    }

    public function showSignup()
    {
        if (Auth::check()) {
            header('Location: index.php?route=' . $this->dashboardFor(Auth::role()));
            exit;
        }
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        require __DIR__ . '/../Views/auth/signup.php';
    }

    // Register as an employee. The account is created as PENDING, so nobody is logged in
    // and nothing can be seen until the Super Admin approves it.
    public function signup()
    {
        $employeeId = trim($_POST['employee_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $errors = [];

        if ($employeeId === '' || $name === '' || $email === '' || $password === '') {
            $errors[] = 'All fields are required.';
        }
        if ($employeeId !== '' && !preg_match('/^[A-Za-z0-9_-]{2,30}$/', $employeeId)) {
            $errors[] = 'Employee ID may only contain letters, numbers, - and _ (2 to 30 characters).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'Password confirmation does not match.';
        }
        if ($email !== '' && User::emailExists($email)) {
            $errors[] = 'An account with this email already exists.';
        }
        if ($employeeId !== '' && User::employeeIdTaken($employeeId)) {
            $errors[] = 'An account with this Employee ID already exists.';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = ['employee_id' => $employeeId, 'name' => $name, 'email' => $email];
            header('Location: index.php?route=signup');
            exit;
        }

        // Always an employee (role 'applicant') and always pending (is_approved = 0):
        // nobody can register themselves as HR or admin, or skip the approval.
        User::create($name, $email, $password, 'applicant', $employeeId, 0);

        $_SESSION['success'] = 'Thank you, ' . $name . '. Your registration was received and is waiting for approval by the Super Admin. You can log in once it has been approved.';
        header('Location: index.php?route=login');
        exit;
    }

    public function logout()
    {
        Auth::logout();
        header('Location: index.php?route=login');
        exit;
    }

    // ---------- Helper functions ----------

    private function dashboardFor($role)
    {
        if ($role === 'admin') {
            return 'admin.dashboard';
        }
        if ($role === 'hr') {
            return 'hr.dashboard';
        }
        return 'applicant.dashboard';
    }

    // Where to go after login. If a guest clicked "Log in to apply", the
    // link has ?next=... so they come back to that vacancy after logging in.
    // The check only allows letters, numbers and . _ = & - characters, so
    // "next" can never send the user to another website.
    private function pageAfterLogin($role)
    {
        $next = $_GET['next'] ?? '';
        if ($role === 'applicant' && $next !== '' && preg_match('/^[a-zA-Z0-9_.=&-]+$/', $next)) {
            return 'index.php?route=' . $next;
        }
        return 'index.php?route=' . $this->dashboardFor($role);
    }
}

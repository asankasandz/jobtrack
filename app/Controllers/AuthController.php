<?php

// Login and logout. There is NO public sign-up: this is an internal system, so accounts
// are created only by the Super Admin (AdminController::store).
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
        unset($_SESSION['errors']);
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

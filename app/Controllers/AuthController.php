<?php

// Login, sign up and logout.
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

    public function signup()
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $errors = [];

        if ($name === '' || $email === '' || $password === '') {
            $errors[] = 'All fields are required.';
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

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = ['name' => $name, 'email' => $email];
            header('Location: index.php?route=signup');
            exit;
        }

        // Public sign-up always creates an Applicant account (never HR).
        $userId = User::createApplicant($name, $email, $password);
        $user = User::findById($userId);
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

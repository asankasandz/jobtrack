<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Models\User;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            header('Location: index.php?route=' . (Auth::isHr() ? 'hr.dashboard' : 'applicant.dashboard'));
            exit;
        }
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);
        require __DIR__ . '/../Views/auth/login.php';
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors = [];

        if ($email === '' || $password === '') {
            $errors[] = 'Please enter both email and password.';
        } else {
            $user = User::findByEmail($email);
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
        header('Location: ' . $this->redirectTarget($user['role']));
        exit;
    }

    // Supports "Apply" links for guests: they're sent to login/signup with
    // ?next=... and, once authenticated, land back on the page they wanted.
    // Only an internal index.php?route=... path is ever honoured, never an
    // external URL, so this can't be used as an open redirect.
    private function redirectTarget(string $role): string
    {
        $next = $_GET['next'] ?? '';
        if ($role === 'applicant' && $next !== '' && preg_match('/^[a-zA-Z0-9_.=&-]+$/', $next)) {
            return 'index.php?route=' . $next;
        }
        return 'index.php?route=' . ($role === 'hr' ? 'hr.dashboard' : 'applicant.dashboard');
    }

    public function showSignup(): void
    {
        if (Auth::check()) {
            header('Location: index.php?route=' . (Auth::isHr() ? 'hr.dashboard' : 'applicant.dashboard'));
            exit;
        }
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        require __DIR__ . '/../Views/auth/signup.php';
    }

    public function signup(): void
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

        // Public sign-up always creates an Applicant account.
        // HR accounts are seeded directly in the database (see database/seed.php),
        // not created through this form, so a user can never self-assign the HR role.
        $userId = User::createApplicant($name, $email, $password);
        $user = User::findById($userId);
        Auth::login($user);

        header('Location: ' . $this->redirectTarget($user['role']));
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: index.php?route=login');
        exit;
    }
}

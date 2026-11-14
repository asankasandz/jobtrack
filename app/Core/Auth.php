<?php

// Login / session helper. The session remembers who is logged in
// between page loads. requireRole() / requireStaff() are used at the top of
// every protected controller action - this is the server-side security check.
//
// Roles:  applicant  = job seeker
//         hr         = manages vacancies and moves applications through the stages
//         admin      = Super Admin: everything HR can do, plus manage users and reports
class Auth
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login($user)
    {
        // New session ID after login (prevents session fixation attacks).
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
    }

    public static function logout()
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function check()
    {
        return isset($_SESSION['user_id']);
    }

    public static function id()
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function name()
    {
        return $_SESSION['user_name'] ?? null;
    }

    public static function role()
    {
        return $_SESSION['user_role'] ?? null;
    }

    public static function isHr()
    {
        return self::role() === 'hr';
    }

    public static function isAdmin()
    {
        return self::role() === 'admin';
    }

    // HR or Super Admin.
    public static function isStaff()
    {
        return self::isHr() || self::isAdmin();
    }

    public static function isApplicant()
    {
        return self::role() === 'applicant';
    }

    // Not logged in? Go to the login page.
    public static function requireLogin()
    {
        if (!self::check()) {
            header('Location: index.php?route=login');
            exit;
        }

        // Check the account in the database on every protected request, so a
        // deactivated user is logged out at once (even with the session still
        // open) and a role change by the Super Admin applies immediately.
        $user = User::findById($_SESSION['user_id']);
        if ($user === null || $user['is_active'] != 1) {
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
            $_SESSION['errors'] = ['Your account is not active. Please contact the administrator.'];
            header('Location: index.php?route=login');
            exit;
        }
        $_SESSION['user_role'] = $user['role'];
    }

    // Logged in, but with the wrong role? Show "403 Forbidden".
    public static function requireRole($role)
    {
        self::requireLogin();
        if (self::role() !== $role) {
            self::forbidden();
        }
    }

    // HR pages: allowed for HR and for the Super Admin.
    public static function requireStaff()
    {
        self::requireLogin();
        if (!self::isStaff()) {
            self::forbidden();
        }
    }

    private static function forbidden()
    {
        http_response_code(403);
        echo '<h1>403 Forbidden</h1><p>You do not have permission to view this page.</p>';
        echo '<p><a href="index.php">Return home</a></p>';
        exit;
    }

    // ---------- CSRF protection ----------
    // Every form carries a secret token that only our own pages know. A form
    // submitted from another website cannot include it, so it is refused.

    public static function csrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            // random_bytes makes a token that cannot be guessed.
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf_token'];
    }

    // The hidden input to put inside every <form method="post">.
    public static function csrfField()
    {
        return '<input type="hidden" name="csrf_token" value="' . self::csrfToken() . '">';
    }

    // Called once for every POST request (in index.php).
    public static function checkCsrf()
    {
        $sent = $_POST['csrf_token'] ?? '';
        $real = $_SESSION['csrf_token'] ?? '';

        if (!is_string($sent) || $real === '' || !hash_equals($real, $sent)) {
            http_response_code(403);
            echo '<h1>403 Forbidden</h1>';
            echo '<p>The security check failed - the page may have expired. Please go back, reload the page and try again.</p>';
            echo '<p><a href="index.php">Return home</a></p>';
            exit;
        }
    }
}

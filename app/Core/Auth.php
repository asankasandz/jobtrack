<?php

// Login / session helper. The session remembers who is logged in
// between page loads. requireRole() is used at the top of every
// protected controller action - this is the server-side security check.
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
    }

    // Logged in, but with the wrong role? Show "403 Forbidden".
    public static function requireRole($role)
    {
        self::requireLogin();
        if (self::role() !== $role) {
            http_response_code(403);
            echo '<h1>403 Forbidden</h1><p>You do not have permission to view this page.</p>';
            echo '<p><a href="index.php">Return home</a></p>';
            exit;
        }
    }
}

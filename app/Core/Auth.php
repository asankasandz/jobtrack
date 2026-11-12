<?php

namespace App\Core;

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function name(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    public static function isHr(): bool
    {
        return self::role() === 'hr';
    }

    public static function isApplicant(): bool
    {
        return self::role() === 'applicant';
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: index.php?route=login');
            exit;
        }
    }

    public static function requireRole(string $role): void
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

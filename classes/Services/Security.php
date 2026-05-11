<?php

namespace App\Services;

final class Security
{
    public static function bootSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || PHP_SAPI === 'cli') {
            return;
        }

        session_set_cookie_params([
            'httponly' => true,
            'secure' => isset($_SERVER['HTTPS']),
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function csrfToken(): string
    {
        self::bootSession();
        $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
        return $_SESSION['_csrf'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        self::bootSession();
        return is_string($token) && isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
    }

    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

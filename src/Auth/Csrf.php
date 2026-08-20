<?php

namespace IMatchBetter\Auth;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . h(self::token()) . '">';
    }

    public static function verify(?string $token): bool
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function verifyRequestOrFail(): void
    {
        if (!self::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            exit('Invalid or expired form submission. Please go back and try again.');
        }
    }
}

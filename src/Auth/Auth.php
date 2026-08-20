<?php

namespace IMatchBetter\Auth;

use IMatchBetter\Models\User;

class Auth
{
    /**
     * @return array{ok:bool, error?:string}
     */
    public static function attempt(string $email, string $password): array
    {
        $user = User::findByEmail($email);

        if (!$user) {
            // Same generic error as a wrong password, so login can't be used to enumerate accounts.
            return ['ok' => false, 'error' => 'Invalid email or password.'];
        }

        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            return ['ok' => false, 'error' => 'Too many failed attempts. Please try again in a few minutes.'];
        }

        if (!$user['is_active']) {
            return ['ok' => false, 'error' => 'This account has been deactivated. Contact support.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            User::registerFailedAttempt((int) $user['id']);
            return ['ok' => false, 'error' => 'Invalid email or password.'];
        }

        User::resetFailedAttempts((int) $user['id']);

        // Regenerate the session ID on every successful login to prevent session fixation.
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];

        return ['ok' => true];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
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

    public static function role(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function fullName(): ?string
    {
        return $_SESSION['full_name'] ?? null;
    }

    /**
     * Checked fresh against the DB each call (not cached in session) since verification
     * can happen in a separate tab/request while this session is still open.
     */
    public static function isEmailVerified(): bool
    {
        $id = self::id();

        return $id !== null && User::isEmailVerified($id);
    }

    public static function dashboardPathForRole(string $role): string
    {
        return match ($role) {
            'applicant' => '/applicant/dashboard.php',
            'employer' => '/employer/dashboard.php',
            'admin' => '/admin/dashboard.php',
            default => '/login.php',
        };
    }
}

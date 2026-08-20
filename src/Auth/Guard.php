<?php

namespace IMatchBetter\Auth;

use IMatchBetter\Models\EmployerProfile;

class Guard
{
    public static function requireLogin(): void
    {
        if (!Auth::check()) {
            flash('error', 'Please log in to continue.');
            redirect('/login.php');
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();

        if (Auth::role() !== $role) {
            http_response_code(403);
            exit('You do not have access to this page.');
        }
    }

    public static function requireApproved(): void
    {
        self::requireRole('employer');

        if (!EmployerProfile::isApproved((int) Auth::id())) {
            redirect('/employer/pending-approval.php');
        }
    }

    public static function requireGuest(): void
    {
        if (Auth::check()) {
            redirect(Auth::dashboardPathForRole((string) Auth::role()));
        }
    }

    /**
     * Gates trust-sensitive actions (applying, reviewing, filing complaints) behind a
     * confirmed email address, without blocking login/browsing for unverified accounts.
     */
    public static function requireVerified(): void
    {
        self::requireLogin();

        if (!Auth::isEmailVerified()) {
            flash('error', 'Please verify your email address before continuing.');
            redirect('/verify-email-pending.php');
        }
    }
}

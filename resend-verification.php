<?php

require __DIR__ . '/includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\EmailVerification;
use IMatchBetter\Models\User;
use IMatchBetter\Services\Mailer;

Guard::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/verify-email-pending.php');
}

Csrf::verifyRequestOrFail();

if (Auth::isEmailVerified()) {
    redirect(Auth::dashboardPathForRole((string) Auth::role()));
}

$userId = (int) Auth::id();

// Cooldown so this can't be used to hammer a mailbox.
if (!EmailVerification::hasRecentRequest($userId)) {
    $user = User::findById($userId);
    $token = EmailVerification::create($userId);
    $verifyUrl = base_url('verify-email.php?token=' . $token);

    Mailer::send(
        $user['email'],
        $user['full_name'],
        'Confirm your IMatchBetter email',
        'verify-email',
        ['verifyUrl' => $verifyUrl, 'fullName' => $user['full_name']]
    );
}

flash('success', 'If your email address is verifiable, a new link is on its way.');
redirect('/verify-email-pending.php');

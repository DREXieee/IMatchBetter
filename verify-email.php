<?php

require __DIR__ . '/includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Models\EmailVerification;
use IMatchBetter\Models\User;

$token = $_GET['token'] ?? '';
$verificationRow = $token !== '' ? EmailVerification::findValidByToken($token) : null;

if (!$verificationRow) {
    $pageTitle = 'Invalid Verification Link — IMatchBetter';
    require __DIR__ . '/includes/header.php';
    ?>
    <main class="container">
        <div class="form-card">
            <h1>This link has expired</h1>
            <p>Verification links are only valid for 24 hours and can only be used once.</p>
            <?php if (Auth::check()): ?>
                <a href="<?= h(base_url('verify-email-pending.php')) ?>" class="btn btn-primary btn-block">Request a new link</a>
            <?php else: ?>
                <a href="<?= h(base_url('auth.php?tab=login')) ?>" class="btn btn-primary btn-block">Log in to request a new link</a>
            <?php endif; ?>
        </div>
    </main>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

User::markEmailVerified((int) $verificationRow['user_id']);
EmailVerification::invalidateAllForUser((int) $verificationRow['user_id']);

flash('success', 'Your email is verified. Thanks!');
redirect(Auth::check() ? Auth::dashboardPathForRole((string) Auth::role()) : '/login.php');

<?php

require __DIR__ . '/includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;

Guard::requireLogin();

if (Auth::isEmailVerified()) {
    redirect(Auth::dashboardPathForRole((string) Auth::role()));
}

$pageTitle = 'Verify Your Email — IMatchBetter';
require __DIR__ . '/includes/header.php';
?>
<main class="container" style="padding: 3rem 0; max-width: 640px;">
    <div class="card">
        <h1>Please verify your email</h1>
        <p>We sent a verification link to your email address when you signed up. Click it to unlock applying to jobs, leaving reviews, and filing reports.</p>
        <p>Didn't get it, or the link expired?</p>
        <form method="post" action="<?= h(base_url('resend-verification.php')) ?>">
            <?= \IMatchBetter\Auth\Csrf::field() ?>
            <button type="submit" class="btn btn-primary">Resend verification email</button>
        </form>
        <p style="margin-top:1rem;"><a href="<?= h(base_url(Auth::dashboardPathForRole((string) Auth::role()))) ?>">Back to dashboard</a></p>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

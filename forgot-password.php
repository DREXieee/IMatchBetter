<?php

require __DIR__ . '/includes/bootstrap.php';

use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Notification;
use IMatchBetter\Models\PasswordReset;
use IMatchBetter\Models\PasswordResetRequest;
use IMatchBetter\Models\User;
use IMatchBetter\Services\Mailer;

Guard::requireGuest();

$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    $email = trim($_POST['email'] ?? '');
    $user = User::findByEmail($email);

    // Cooldown + generic response either way, so this endpoint can't be used to enumerate
    // accounts or be hammered to spam a target's inbox (or admins) with duplicate requests.
    if ($user && $user['role'] === 'employer') {
        // Employers go through admin approval (extra security gate) — no token is generated
        // and no email is sent here; an admin must approve first (see
        // admin/password-resets/approve.php), which is where PasswordReset::create() runs.
        if (!PasswordResetRequest::hasRecentRequest((int) $user['id'])) {
            $requestId = PasswordResetRequest::create((int) $user['id']);

            foreach (Notification::adminUserIds() as $adminId) {
                Notification::create(
                    (int) $adminId,
                    'password_reset_requested',
                    "{$user['full_name']} ({$user['email']}) requested a password reset.",
                    $requestId,
                    'password_reset_request'
                );
            }
        }
    } elseif ($user && !PasswordReset::hasRecentRequest((int) $user['id'])) {
        // Applicants and admins keep the normal instant reset — no approval gate.
        $token = PasswordReset::create((int) $user['id']);
        $resetUrl = base_url('reset-password.php?token=' . $token);

        Mailer::send(
            $user['email'],
            $user['full_name'],
            'Reset your IMatchBetter password',
            'password-reset',
            ['resetUrl' => $resetUrl]
        );
    }

    $submitted = true;
}

$pageTitle = 'Forgot Password — IMatchBetter';
require __DIR__ . '/includes/header.php';
?>
<main class="container">
    <div class="form-card">
        <h1>Forgot your password?</h1>

        <?php if ($submitted): ?>
            <p>If an account exists for that email, we've sent a password reset link. It expires in 30 minutes.</p>
            <a href="<?= h(base_url('auth.php?tab=login')) ?>" class="btn btn-outline btn-block">Back to Log In</a>
        <?php else: ?>
            <p class="form-hint">Enter your email and we'll send you a reset link.</p>
            <form method="post" action="<?= h(base_url('forgot-password.php')) ?>" novalidate>
                <?= Csrf::field() ?>
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
            </form>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

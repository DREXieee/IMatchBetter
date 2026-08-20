<?php

require __DIR__ . '/includes/bootstrap.php';

use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Helpers\Validator;
use IMatchBetter\Models\PasswordReset;
use IMatchBetter\Models\User;

Guard::requireGuest();

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$resetRow = $token !== '' ? PasswordReset::findValidByToken($token) : null;
$errors = [];

if (!$resetRow) {
    $pageTitle = 'Invalid Reset Link — IMatchBetter';
    require __DIR__ . '/includes/header.php';
    ?>
    <main class="container">
        <div class="form-card">
            <h1>This link has expired</h1>
            <p>Password reset links are only valid for 30 minutes and can only be used once.</p>
            <a href="<?= h(base_url('forgot-password.php')) ?>" class="btn btn-primary btn-block">Request a new link</a>
        </div>
    </main>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!Validator::isStrongPassword($password)) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        User::updatePassword((int) $resetRow['user_id'], $password);
        PasswordReset::invalidateAllForUser((int) $resetRow['user_id']);

        flash('success', 'Your password has been reset. Please log in.');
        redirect('/login.php');
    }
}

$pageTitle = 'Reset Password — IMatchBetter';
require __DIR__ . '/includes/header.php';
?>
<main class="container">
    <div class="form-card">
        <h1>Choose a new password</h1>

        <form method="post" action="<?= h(base_url('reset-password.php')) ?>" novalidate>
            <?= Csrf::field() ?>
            <input type="hidden" name="token" value="<?= h($token) ?>">

            <div class="form-group">
                <label class="form-label" for="password">New password</label>
                <input class="form-control" type="password" id="password" name="password" required minlength="8">
                <?php if (!empty($errors['password'])): ?><div class="form-error"><?= h($errors['password']) ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm new password</label>
                <input class="form-control" type="password" id="confirm_password" name="confirm_password" required minlength="8">
                <?php if (!empty($errors['confirm_password'])): ?><div class="form-error"><?= h($errors['confirm_password']) ?></div><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
        </form>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

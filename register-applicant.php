<?php

require __DIR__ . '/includes/bootstrap.php';

use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;

Guard::requireGuest();

$errors = [];
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();
    require __DIR__ . '/includes/handlers/register-applicant-handler.php';
}

$pageTitle = 'Sign Up as Applicant — IMatchBetter';
require __DIR__ . '/includes/header.php';
?>
<main class="container">
    <div class="form-card">
        <h1>Create your applicant account</h1>

        <form method="post" action="<?= h(base_url('register-applicant.php')) ?>" novalidate>
            <?= \IMatchBetter\Auth\Csrf::field() ?>

            <div class="form-group">
                <label class="form-label" for="full_name">Full name</label>
                <input class="form-control" type="text" id="full_name" name="full_name" value="<?= h($fullName) ?>" required>
                <?php if (!empty($errors['full_name'])): ?><div class="form-error"><?= h($errors['full_name']) ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= h($email) ?>" required>
                <?php if (!empty($errors['email'])): ?><div class="form-error"><?= h($errors['email']) ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password" required minlength="8">
                <div class="form-hint">At least 8 characters.</div>
                <?php if (!empty($errors['password'])): ?><div class="form-error"><?= h($errors['password']) ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm password</label>
                <input class="form-control" type="password" id="confirm_password" name="confirm_password" required minlength="8">
                <?php if (!empty($errors['confirm_password'])): ?><div class="form-error"><?= h($errors['confirm_password']) ?></div><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create account</button>
        </form>

        <p style="margin-top:1rem;">Already have an account? <a href="<?= h(base_url('auth.php?tab=login')) ?>">Log in</a></p>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

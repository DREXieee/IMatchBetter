<?php

require __DIR__ . '/includes/bootstrap.php';

use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;

Guard::requireGuest();

$errors = [];
$fullName = '';
$email = '';
$companyName = '';
$companyWebsite = '';
$companyDescription = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();
    require __DIR__ . '/includes/handlers/register-employer-handler.php';
}

$pageTitle = 'Sign Up as Employer — IMatchBetter';
require __DIR__ . '/includes/header.php';
?>
<main class="container">
    <div class="form-card" style="max-width:560px;">
        <h1>Register your company</h1>
        <p class="form-hint">An admin will review your request before you can post jobs.</p>

        <form method="post" action="<?= h(base_url('register-employer.php')) ?>" novalidate>
            <?= \IMatchBetter\Auth\Csrf::field() ?>

            <div class="form-group">
                <label class="form-label" for="full_name">Your full name</label>
                <input class="form-control" type="text" id="full_name" name="full_name" value="<?= h($fullName) ?>" required>
                <?php if (!empty($errors['full_name'])): ?><div class="form-error"><?= h($errors['full_name']) ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Work email</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= h($email) ?>" required>
                <?php if (!empty($errors['email'])): ?><div class="form-error"><?= h($errors['email']) ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password" required minlength="8">
                <?php if (!empty($errors['password'])): ?><div class="form-error"><?= h($errors['password']) ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm password</label>
                <input class="form-control" type="password" id="confirm_password" name="confirm_password" required minlength="8">
                <?php if (!empty($errors['confirm_password'])): ?><div class="form-error"><?= h($errors['confirm_password']) ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="company_name">Company name</label>
                <input class="form-control" type="text" id="company_name" name="company_name" value="<?= h($companyName) ?>" required>
                <?php if (!empty($errors['company_name'])): ?><div class="form-error"><?= h($errors['company_name']) ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="company_website">Company website (optional)</label>
                <input class="form-control" type="url" id="company_website" name="company_website" value="<?= h($companyWebsite) ?>" placeholder="https://">
            </div>

            <div class="form-group">
                <label class="form-label" for="company_description">Short company description (optional)</label>
                <textarea class="form-control" id="company_description" name="company_description" rows="3"><?= h($companyDescription) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Submit for approval</button>
        </form>

        <p style="margin-top:1rem;">Already have an account? <a href="<?= h(base_url('auth.php?tab=login')) ?>">Log in</a></p>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

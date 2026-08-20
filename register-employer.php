<?php

require __DIR__ . '/includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Helpers\Validator;
use IMatchBetter\Models\EmailVerification;
use IMatchBetter\Models\EmployerProfile;
use IMatchBetter\Models\Notification;
use IMatchBetter\Models\User;
use IMatchBetter\Services\Mailer;

Guard::requireGuest();

$errors = [];
$fullName = '';
$email = '';
$companyName = '';
$companyWebsite = '';
$companyDescription = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $companyName = trim($_POST['company_name'] ?? '');
    $companyWebsite = trim($_POST['company_website'] ?? '');
    $companyDescription = trim($_POST['company_description'] ?? '');

    if ($fullName === '') {
        $errors['full_name'] = 'Full name is required.';
    }
    if (!Validator::isEmail($email)) {
        $errors['email'] = 'Enter a valid email address.';
    } elseif (User::emailExists($email)) {
        $errors['email'] = 'An account with this email already exists.';
    }
    if (!Validator::isStrongPassword($password)) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }
    if ($companyName === '') {
        $errors['company_name'] = 'Company name is required.';
    }

    if (empty($errors)) {
        $userId = User::create($email, $password, 'employer', $fullName);
        EmployerProfile::create($userId, $companyName, $companyWebsite ?: null, $companyDescription ?: null);

        foreach (Notification::adminUserIds() as $adminId) {
            Notification::create(
                (int) $adminId,
                'employer_registered',
                "New employer request from {$companyName} needs review.",
                $userId,
                'employer_profile'
            );
        }

        $token = EmailVerification::create($userId);
        Mailer::send(
            $email,
            $fullName,
            'Confirm your IMatchBetter email',
            'verify-email',
            ['verifyUrl' => base_url('verify-email.php?token=' . $token), 'fullName' => $fullName]
        );

        Auth::attempt($email, $password);
        flash('info', 'Your employer account was created. Check your email to verify your account — an admin also needs to approve it before you can post jobs.');
        redirect('/employer/pending-approval.php');
    }
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

        <p style="margin-top:1rem;">Already have an account? <a href="<?= h(base_url('login.php')) ?>">Log in</a></p>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

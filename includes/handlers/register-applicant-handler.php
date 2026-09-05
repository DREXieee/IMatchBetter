<?php

/**
 * Applicant account creation. Expects $_POST: full_name, email, password,
 * confirm_password. Populates $errors (array) on failure; on success creates
 * the user + empty profile, sends the verification email, logs the user in,
 * and redirects — the caller (auth.php or register-applicant.php) must have
 * already verified the request is a POST for this role.
 *
 * Included, not required standalone — expects Csrf::verifyRequestOrFail()
 * to have already run in the including file.
 */

use IMatchBetter\Auth\Auth;
use IMatchBetter\Helpers\Validator;
use IMatchBetter\Models\ApplicantProfile;
use IMatchBetter\Models\EmailVerification;
use IMatchBetter\Models\User;
use IMatchBetter\Services\Mailer;

$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

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

if (empty($errors)) {
    $userId = User::create($email, $password, 'applicant', $fullName);
    ApplicantProfile::create($userId);

    if (($accountType ?? '') === 'experienced') {
        ApplicantProfile::update($userId, ['experience_level' => 'Experienced Professional']);
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
    flash('success', 'Welcome to IMatchBetter! Check your email to verify your account.');
    redirect('/applicant/profile-wizard.php?new=1');
}

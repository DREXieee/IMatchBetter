<?php

/**
 * Employer account creation. Expects $_POST: full_name, email, password,
 * confirm_password, company_name, company_website (optional),
 * company_description (optional); and $_FILES: valid_id, company_photo
 * (both required, for admin verification). Populates $errors (array) on
 * failure; on success creates the user + employer profile, notifies admins,
 * sends the verification email, logs the user in, and redirects to the
 * pending-approval screen — the caller must have already verified the
 * request is a POST for this role.
 *
 * Included, not required standalone — expects Csrf::verifyRequestOrFail()
 * to have already run in the including file.
 */

use IMatchBetter\Auth\Auth;
use IMatchBetter\Helpers\Validator;
use IMatchBetter\Models\EmailVerification;
use IMatchBetter\Models\EmployerProfile;
use IMatchBetter\Models\Notification;
use IMatchBetter\Models\User;
use IMatchBetter\Services\FileUploadService;
use IMatchBetter\Services\Mailer;

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

if (empty($_FILES['valid_id']) || ($_FILES['valid_id']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    $errors['valid_id'] = 'Please upload a valid ID or company registration document.';
} else {
    try {
        FileUploadService::validateValidId($_FILES['valid_id']);
    } catch (\RuntimeException $e) {
        $errors['valid_id'] = $e->getMessage();
    }
}

if (empty($_FILES['company_photo']) || ($_FILES['company_photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    $errors['company_photo'] = 'Please upload a photo of your company environment.';
} else {
    try {
        FileUploadService::validateCompanyPhoto($_FILES['company_photo']);
    } catch (\RuntimeException $e) {
        $errors['company_photo'] = $e->getMessage();
    }
}

if (empty($errors)) {
    $validId = FileUploadService::storeValidId($_FILES['valid_id']);
    $companyPhoto = FileUploadService::storeCompanyPhoto($_FILES['company_photo']);

    $userId = User::create($email, $password, 'employer', $fullName);
    EmployerProfile::create($userId, $companyName, $companyWebsite ?: null, $companyDescription ?: null, $validId['file_path'], $companyPhoto['file_path']);

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

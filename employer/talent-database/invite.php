<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\EmployerProfile;
use IMatchBetter\Models\Notification;
use IMatchBetter\Models\User;

Guard::requireApproved();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/employer/talent-database.php');
}

Csrf::verifyRequestOrFail();

$employerId = (int) Auth::id();
$applicantId = (int) ($_POST['applicant_id'] ?? 0);
$applicant = $applicantId > 0 ? User::findById($applicantId) : null;

if ($applicant && $applicant['role'] === 'applicant') {
    $profile = EmployerProfile::findByUserId($employerId);
    $companyName = $profile['company_name'] ?? 'An employer';

    Notification::create(
        $applicantId,
        'employer_invite',
        "{$companyName} invited you to apply to one of their open roles.",
        $employerId,
        'employer_profile'
    );

    flash('success', 'Invitation sent to ' . $applicant['full_name'] . '.');
}

redirect('/employer/talent-database.php');

<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\EmployerProfile;
use IMatchBetter\Models\Notification;

Guard::requireRole('employer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/employer/pending-approval.php');
}

Csrf::verifyRequestOrFail();

$profile = EmployerProfile::findByUserId((int) Auth::id());

if (!$profile || $profile['approval_status'] !== 'rejected') {
    flash('error', 'Only rejected requests can be resubmitted.');
    redirect('/employer/pending-approval.php');
}

EmployerProfile::resubmit((int) $profile['id']);

foreach (Notification::adminUserIds() as $adminId) {
    Notification::create(
        (int) $adminId,
        'employer_resubmitted',
        "{$profile['company_name']} resubmitted their employer request for review.",
        (int) $profile['id'],
        'employer_profile'
    );
}

flash('success', 'Your request has been resubmitted for review.');
redirect('/employer/pending-approval.php');

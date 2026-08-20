<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\EmployerProfile;
use IMatchBetter\Models\Notification;
use IMatchBetter\Services\AuditLogger;
use IMatchBetter\Services\Mailer;

Guard::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/employers/pending.php');
}

Csrf::verifyRequestOrFail();

$id = (int) ($_POST['id'] ?? 0);
$profile = EmployerProfile::find($id);

if (!$profile) {
    flash('error', 'Employer request not found.');
    redirect('/admin/employers/pending.php');
}

EmployerProfile::approve($id, (int) Auth::id());
AuditLogger::log((int) Auth::id(), 'employer_approved', 'employer_profile', $id, $profile['company_name']);
$notificationId = Notification::create(
    (int) $profile['user_id'],
    'employer_approved',
    'Your company ' . $profile['company_name'] . ' has been approved. You can now post jobs.',
    $id,
    'employer_profile'
);

$emailSent = Mailer::send(
    $profile['email'],
    $profile['full_name'],
    'Your IMatchBetter employer account is approved',
    'employer-approved',
    ['companyName' => $profile['company_name']]
);
if ($emailSent) {
    Notification::markEmailSent($notificationId);
}

flash('success', $profile['company_name'] . ' has been approved.');
redirect('/admin/employers/pending.php');

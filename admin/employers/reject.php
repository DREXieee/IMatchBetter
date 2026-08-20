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
$reason = trim($_POST['reason'] ?? '');
$profile = EmployerProfile::find($id);

if (!$profile) {
    flash('error', 'Employer request not found.');
    redirect('/admin/employers/pending.php');
}

if ($reason === '') {
    $reason = 'No reason provided.';
}

EmployerProfile::reject($id, (int) Auth::id(), $reason);
AuditLogger::log((int) Auth::id(), 'employer_rejected', 'employer_profile', $id, $reason);
$notificationId = Notification::create(
    (int) $profile['user_id'],
    'employer_rejected',
    'Your request for ' . $profile['company_name'] . ' was not approved: ' . $reason,
    $id,
    'employer_profile'
);

$emailSent = Mailer::send(
    $profile['email'],
    $profile['full_name'],
    'Update on your IMatchBetter employer request',
    'employer-rejected',
    ['companyName' => $profile['company_name'], 'reason' => $reason]
);
if ($emailSent) {
    Notification::markEmailSent($notificationId);
}

flash('info', $profile['company_name'] . ' has been rejected.');
redirect('/admin/employers/pending.php');

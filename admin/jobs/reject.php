<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Job;
use IMatchBetter\Models\Notification;
use IMatchBetter\Models\User;
use IMatchBetter\Services\AuditLogger;
use IMatchBetter\Services\Mailer;

Guard::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/jobs/pending.php');
}

Csrf::verifyRequestOrFail();

$id = (int) ($_POST['id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$job = Job::find($id);

if (!$job || $job['approval_status'] !== 'pending') {
    flash('error', 'Job not found or already handled.');
    redirect('/admin/jobs/pending.php');
}

if ($reason === '') {
    $reason = 'No reason provided.';
}

Job::reject($id, (int) Auth::id(), $reason);
AuditLogger::log((int) Auth::id(), 'job_rejected', 'job', $id, $reason);

$notificationId = Notification::create(
    (int) $job['employer_id'],
    'job_rejected',
    'Your job posting "' . $job['title'] . '" was not approved: ' . $reason,
    $id,
    'job'
);

$employer = User::findById((int) $job['employer_id']);
if ($employer) {
    $emailSent = Mailer::send(
        $employer['email'],
        $employer['full_name'],
        'Update on your job posting',
        'job-rejected',
        ['jobTitle' => $job['title'], 'reason' => $reason]
    );
    if ($emailSent) {
        Notification::markEmailSent($notificationId);
    }
}

flash('info', 'Job rejected.');
redirect('/admin/jobs/pending.php');

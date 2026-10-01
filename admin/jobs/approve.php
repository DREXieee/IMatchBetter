<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Job;
use IMatchBetter\Models\JobMatchQueue;
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
$job = Job::find($id);

if (!$job || $job['approval_status'] !== 'pending') {
    flash('error', 'Job not found or already handled.');
    redirect('/admin/jobs/pending.php');
}

Job::approve($id, (int) Auth::id());
AuditLogger::log((int) Auth::id(), 'job_approved', 'job', $id, $job['title']);

// Deferred from create/edit time — scoring only needs to run once the job is truly public.
JobMatchQueue::enqueue($id);

$employer = User::findById((int) $job['employer_id']);
$notificationId = Notification::create(
    (int) $job['employer_id'],
    'job_approved',
    'Your job posting "' . $job['title'] . '" has been approved and is now live.',
    $id,
    'job'
);

if ($employer) {
    $updated = Job::find($id);
    $emailSent = Mailer::send(
        $employer['email'],
        $employer['full_name'],
        'Your job posting is now live',
        'job-approved',
        ['jobTitle' => $job['title'], 'jobUrl' => base_url('job-view.php?slug=' . $updated['slug'])]
    );
    if ($emailSent) {
        Notification::markEmailSent($notificationId);
    }
}

flash('success', 'Job approved and published.');
redirect('/admin/jobs/pending.php');

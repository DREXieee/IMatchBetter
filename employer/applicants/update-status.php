<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Application;
use IMatchBetter\Models\Notification;
use IMatchBetter\Models\User;
use IMatchBetter\Services\Mailer;

Guard::requireRole('employer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/employer/dashboard.php');
}

Csrf::verifyRequestOrFail();

$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$validStatuses = ['submitted', 'reviewed', 'interview', 'rejected', 'hired'];

if (!Application::isForEmployer($id, (int) Auth::id()) || !in_array($status, $validStatuses, true)) {
    http_response_code(404);
    exit('Application not found.');
}

$application = Application::findWithContext($id);

Application::updateStatus($id, $status, (int) Auth::id());

$notificationId = Notification::create(
    (int) ($application['applicant_id'] ?? 0),
    'application_status_changed',
    'Your application for ' . $application['job_title'] . ' is now: ' . $status . '.',
    $id,
    'application'
);

$applicantUser = User::findById((int) $application['applicant_id']);
$emailSent = Mailer::send(
    $applicantUser['email'],
    $applicantUser['full_name'],
    'Your application status has changed',
    'application-status-changed',
    ['applicantName' => $applicantUser['full_name'], 'jobTitle' => $application['job_title'], 'status' => $status]
);
if ($emailSent) {
    Notification::markEmailSent($notificationId);
}

flash('success', 'Application status updated.');
redirect('/employer/applicants/view.php?id=' . $id);

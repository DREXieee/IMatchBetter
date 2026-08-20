<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Application;
use IMatchBetter\Models\EmployerProfile;
use IMatchBetter\Models\Interview;
use IMatchBetter\Models\Notification;
use IMatchBetter\Models\User;
use IMatchBetter\Services\Mailer;

Guard::requireRole('employer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/employer/dashboard.php');
}

Csrf::verifyRequestOrFail();

$applicationId = (int) ($_POST['application_id'] ?? 0);

if (!Application::isForEmployer($applicationId, (int) Auth::id())) {
    http_response_code(404);
    exit('Application not found.');
}

$scheduledAt = trim($_POST['scheduled_at'] ?? '');
$mode = $_POST['mode'] ?? '';
$locationOrLink = trim($_POST['location_or_link'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($scheduledAt === '' || !in_array($mode, ['onsite', 'video', 'phone'], true)) {
    flash('error', 'Please provide a date/time and interview mode.');
    redirect('/employer/applicants/view.php?id=' . $applicationId);
}

$scheduledAtSql = date('Y-m-d H:i:s', strtotime($scheduledAt));

$application = Application::findWithContext($applicationId);
$existing = Interview::findByApplication($applicationId);

if ($existing) {
    Interview::reschedule($existing['id'], $scheduledAtSql, $mode, $locationOrLink ?: null, $notes ?: null);
} else {
    Interview::create($applicationId, $scheduledAtSql, $mode, $locationOrLink ?: null, $notes ?: null, (int) Auth::id());
}

$notificationId = Notification::create(
    (int) $application['applicant_id'],
    'interview_scheduled',
    'You have an interview for ' . $application['job_title'] . ' on ' . date('M j, Y g:i A', strtotime($scheduledAtSql)) . '.',
    $applicationId,
    'application'
);

$applicantUser = User::findById((int) $application['applicant_id']);
$employerProfile = EmployerProfile::findByUserId((int) Auth::id());
$emailSent = Mailer::send(
    $applicantUser['email'],
    $applicantUser['full_name'],
    'Interview invitation — ' . $application['job_title'],
    'interview-scheduled',
    [
        'applicantName' => $applicantUser['full_name'],
        'jobTitle' => $application['job_title'],
        'companyName' => $employerProfile['company_name'] ?? 'the employer',
        'scheduledAt' => date('M j, Y g:i A', strtotime($scheduledAtSql)),
        'mode' => $mode,
        'locationOrLink' => $locationOrLink ?: null,
    ]
);
if ($emailSent) {
    Notification::markEmailSent($notificationId);
}

flash('success', 'Interview scheduled.');
redirect('/employer/applicants/view.php?id=' . $applicationId);

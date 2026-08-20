<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Application;
use IMatchBetter\Models\Interview;
use IMatchBetter\Models\Notification;
use IMatchBetter\Models\User;
use IMatchBetter\Services\Mailer;

Guard::requireRole('applicant');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/applicant/interviews/index.php');
}

Csrf::verifyRequestOrFail();

$id = (int) ($_POST['id'] ?? 0);
$response = $_POST['response'] ?? '';

if (!Interview::isForApplicant($id, (int) Auth::id()) || !in_array($response, ['confirmed', 'declined'], true)) {
    http_response_code(404);
    exit('Interview not found.');
}

$interview = Interview::find($id);
Interview::updateStatus($id, $response, true);

$application = Application::findWithContext((int) $interview['application_id']);
$employerUser = User::findById((int) $application['employer_id']);

$notificationId = Notification::create(
    (int) $application['employer_id'],
    'interview_response',
    $application['applicant_name'] . ' has ' . $response . ' the interview for ' . $application['job_title'] . '.',
    (int) $interview['application_id'],
    'application'
);

$emailSent = Mailer::send(
    $employerUser['email'],
    $employerUser['full_name'],
    'Interview response — ' . $application['job_title'],
    'interview-response',
    ['applicantName' => $application['applicant_name'], 'jobTitle' => $application['job_title'], 'status' => $response]
);
if ($emailSent) {
    Notification::markEmailSent($notificationId);
}

flash('success', 'Interview ' . $response . '.');
redirect('/applicant/interviews/index.php');

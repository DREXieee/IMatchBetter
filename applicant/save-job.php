<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Job;
use IMatchBetter\Models\SavedJob;

Guard::requireRole('applicant');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/applicant/saved-jobs.php');
}

Csrf::verifyRequestOrFail();

$jobId = (int) ($_POST['job_id'] ?? 0);

if (Job::find($jobId)) {
    if (($_POST['action'] ?? 'save') === 'unsave') {
        SavedJob::unsave((int) Auth::id(), $jobId);
    } else {
        SavedJob::save((int) Auth::id(), $jobId);
    }
}

// Only follow the redirect hint if it's a same-app relative path, to avoid an open redirect.
// Backslashes are rejected too since some browsers normalize "/\evil.com" into "//evil.com".
$redirectTo = (string) ($_POST['redirect'] ?? '');
if (preg_match('#^/(?![/\\\\])[^\r\n]*$#', $redirectTo) && !str_contains($redirectTo, '://') && !str_contains($redirectTo, '\\')) {
    header('Location: ' . $redirectTo);
    exit;
}

redirect('/applicant/saved-jobs.php');

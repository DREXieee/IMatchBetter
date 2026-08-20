<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Resume;

Guard::requireRole('applicant');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/applicant/profile.php');
}

Csrf::verifyRequestOrFail();

$id = (int) ($_POST['id'] ?? 0);
$resume = Resume::find($id);

if (!$resume || !Resume::isOwnedBy($id, (int) Auth::id())) {
    http_response_code(404);
    exit('Resume not found.');
}

if (!Resume::delete($id)) {
    flash('error', 'This resume has already been submitted with an application and can\'t be deleted.');
    redirect('/applicant/profile.php');
}

$fullPath = BASE_PATH . '/' . $resume['file_path'];
if (is_file($fullPath)) {
    unlink($fullPath);
}

flash('success', 'Resume deleted.');
redirect('/applicant/profile.php');

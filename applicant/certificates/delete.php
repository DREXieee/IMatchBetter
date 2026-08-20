<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Certificate;

Guard::requireRole('applicant');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/applicant/profile.php');
}

Csrf::verifyRequestOrFail();

$id = (int) ($_POST['id'] ?? 0);
$certificate = Certificate::find($id);

if (!$certificate || !Certificate::isOwnedBy($id, (int) Auth::id())) {
    http_response_code(404);
    exit('Certificate not found.');
}

Certificate::delete($id);

$fullPath = BASE_PATH . '/' . $certificate['file_path'];
if (is_file($fullPath)) {
    unlink($fullPath);
}

flash('success', 'Certificate deleted.');
redirect('/applicant/profile.php');

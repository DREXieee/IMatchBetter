<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Job;

Guard::requireApproved();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/employer/jobs/index.php');
}

Csrf::verifyRequestOrFail();

$id = (int) ($_POST['id'] ?? 0);

if (!Job::isOwnedBy($id, (int) Auth::id())) {
    http_response_code(404);
    exit('Job not found.');
}

Job::close($id);
flash('success', 'Job posting closed.');
redirect('/employer/jobs/index.php');

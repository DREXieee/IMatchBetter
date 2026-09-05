<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\ApplicantReview;
use IMatchBetter\Models\EmployerReview;
use IMatchBetter\Models\Notification;
use IMatchBetter\Services\AuditLogger;

Guard::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/reviews/index.php');
}

Csrf::verifyRequestOrFail();

$type = $_POST['type'] ?? '';
$id = (int) ($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!in_array($type, ['employer', 'applicant'], true) || !in_array($action, ['approve', 'reject'], true)) {
    redirect('/admin/reviews/index.php');
}

$status = $action === 'approve' ? 'approved' : 'rejected';
$adminId = (int) Auth::id();
$found = false;

if ($type === 'employer') {
    $review = EmployerReview::find($id);
    if ($review) {
        $found = true;
        EmployerReview::moderate($id, $status, $adminId);
        AuditLogger::log($adminId, 'employer_review_' . $status, 'employer_review', $id);

        if ($status === 'approved') {
            Notification::create(
                (int) $review['employer_id'],
                'review_posted',
                'A new review of your company was approved.',
                $id,
                'employer_review'
            );
        }
    }
} else {
    $review = ApplicantReview::find($id);
    if ($review) {
        $found = true;
        ApplicantReview::moderate($id, $status, $adminId);
        AuditLogger::log($adminId, 'applicant_review_' . $status, 'applicant_review', $id);

        if ($status === 'approved') {
            Notification::create(
                (int) $review['applicant_id'],
                'review_posted',
                'A new review from an employer was approved.',
                $id,
                'applicant_review'
            );
        }
    }
}

if ($found) {
    flash('success', 'Review ' . $status . '.');
} else {
    flash('error', 'Review not found.');
}
redirect('/admin/reviews/index.php');

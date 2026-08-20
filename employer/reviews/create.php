<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\ApplicantReview;
use IMatchBetter\Models\Application;

Guard::requireRole('employer');
Guard::requireVerified();

$employerId = (int) Auth::id();
$applicationId = (int) ($_GET['application_id'] ?? $_POST['application_id'] ?? 0);
$application = Application::findWithContext($applicationId);

if (!$application || !Application::isForEmployer($applicationId, $employerId)) {
    http_response_code(404);
    exit('Application not found.');
}

$applicantId = (int) $application['applicant_id'];
$errors = [];

if (ApplicantReview::hasReviewed($employerId, $applicantId)) {
    flash('info', 'You have already reviewed this applicant.');
    redirect('/employer/applicants/view.php?id=' . $applicationId);
}

const REVIEWS_PER_DAY = 10;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    if (ApplicantReview::countRecentByAuthor($employerId) >= REVIEWS_PER_DAY) {
        flash('error', "You've reached the daily limit for submitting reviews. Please try again tomorrow.");
        redirect('/employer/applicants/view.php?id=' . $applicationId);
    }

    $rating = (int) ($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $errors['rating'] = 'Please choose a rating from 1 to 5.';
    }
    if ($body === '') {
        $errors['body'] = 'Please write a short review.';
    }

    if (empty($errors)) {
        ApplicantReview::create($applicantId, $employerId, $applicationId, $rating, $title ?: null, $body);
        flash('success', 'Review submitted for admin moderation.');
        redirect('/employer/applicants/view.php?id=' . $applicationId);
    }
}

$role = 'employer';
$pageTitle = 'Rate Applicant — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>Rate <?= h($application['applicant_name']) ?></h1>
        <p>For <?= h($application['job_title']) ?></p>

        <form method="post" action="<?= h(base_url('employer/reviews/create.php')) ?>" class="card" style="max-width:640px;">
            <?= Csrf::field() ?>
            <input type="hidden" name="application_id" value="<?= (int) $applicationId ?>">

            <div class="form-group">
                <label class="form-label" for="rating">Rating (1-5)</label>
                <select class="form-control" id="rating" name="rating">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?= $i ?>"><?= str_repeat('★', $i) . str_repeat('☆', 5 - $i) ?></option>
                    <?php endfor; ?>
                </select>
                <?php if (!empty($errors['rating'])): ?><div class="form-error"><?= h($errors['rating']) ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="title">Title (optional)</label>
                <input class="form-control" type="text" id="title" name="title" maxlength="150">
            </div>

            <div class="form-group">
                <label class="form-label" for="body">Review</label>
                <textarea class="form-control" id="body" name="body" rows="4"></textarea>
                <?php if (!empty($errors['body'])): ?><div class="form-error"><?= h($errors['body']) ?></div><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Submit Review</button>
        </form>
    </main>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

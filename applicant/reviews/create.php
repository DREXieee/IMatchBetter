<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Application;
use IMatchBetter\Models\EmployerReview;

Guard::requireRole('applicant');
Guard::requireVerified();

$userId = (int) Auth::id();
$applications = Application::forApplicant($userId);

$employers = [];
foreach ($applications as $app) {
    $employers[(int) $app['employer_id']] = $app['company_name'];
}

$errors = [];
$selectedEmployerId = (int) ($_GET['employer_id'] ?? 0);

const REVIEWS_PER_DAY = 10;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    if (EmployerReview::countRecentByAuthor($userId) >= REVIEWS_PER_DAY) {
        flash('error', "You've reached the daily limit for submitting reviews. Please try again tomorrow.");
        redirect('/applicant/reviews/index.php');
    }

    $employerId = (int) ($_POST['employer_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $selectedEmployerId = $employerId;

    if (!isset($employers[$employerId])) {
        $errors['employer_id'] = 'Please choose an employer you have applied to.';
    } elseif (EmployerReview::hasReviewed($userId, $employerId)) {
        $errors['employer_id'] = 'You have already reviewed this employer.';
    }

    if ($rating < 1 || $rating > 5) {
        $errors['rating'] = 'Please choose a rating from 1 to 5.';
    }
    if ($body === '') {
        $errors['body'] = 'Please write a short review.';
    }

    if (empty($errors)) {
        $applicationId = null;
        foreach ($applications as $app) {
            if ((int) $app['employer_id'] === $employerId) {
                $applicationId = (int) $app['id'];
                break;
            }
        }

        EmployerReview::create($employerId, $userId, $applicationId, $rating, $title ?: null, $body);
        flash('success', 'Review submitted for admin moderation.');
        redirect('/applicant/reviews/index.php');
    }
}

$role = 'applicant';
$pageTitle = 'Review an Employer — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>Review an Employer</h1>

        <?php if (empty($employers)): ?>
            <div class="card empty-state">You can review an employer once you've applied to one of their jobs.</div>
        <?php else: ?>
            <form method="post" action="<?= h(base_url('applicant/reviews/create.php')) ?>" class="card" style="max-width:640px;">
                <?= Csrf::field() ?>

                <div class="form-group">
                    <label class="form-label" for="employer_id">Employer</label>
                    <select class="form-control" id="employer_id" name="employer_id">
                        <option value="">Select an employer</option>
                        <?php foreach ($employers as $employerId => $companyName): ?>
                            <option value="<?= (int) $employerId ?>" <?= $selectedEmployerId === $employerId ? 'selected' : '' ?>><?= h($companyName) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['employer_id'])): ?><div class="form-error"><?= h($errors['employer_id']) ?></div><?php endif; ?>
                </div>

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
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

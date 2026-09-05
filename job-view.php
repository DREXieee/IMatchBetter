<?php

require __DIR__ . '/includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Models\Application;
use IMatchBetter\Models\EmployerReview;
use IMatchBetter\Models\Job;
use IMatchBetter\Models\SavedJob;
use IMatchBetter\Models\Skill;

$slug = $_GET['slug'] ?? '';
$job = $slug !== '' ? Job::findBySlug($slug) : null;

if (!$job || $job['status'] !== 'open') {
    http_response_code(404);
    require __DIR__ . '/includes/bootstrap.php';
    $pageTitle = 'Job Not Found — IMatchBetter';
    require __DIR__ . '/includes/header.php';
    echo '<main class="container" style="padding:3rem 0; text-align:center;"><h1>Job not found</h1><p>This job may have been closed or removed.</p><a href="' . h(base_url('jobs.php')) . '" class="btn btn-primary">Browse other jobs</a></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$employmentLabels = ['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'internship' => 'Internship', 'remote' => 'Remote'];
$isApplicant = Auth::check() && Auth::role() === 'applicant';
$alreadyApplied = $isApplicant && Application::hasApplied((int) Auth::id(), (int) $job['id']);
$isJobSaved = $isApplicant && SavedJob::isSaved((int) Auth::id(), (int) $job['id']);
$reviewStats = EmployerReview::statsForEmployer((int) $job['employer_id']);
$jobSkills = Skill::namesForJob((int) $job['id']);

$pageTitle = $job['title'] . ' at ' . $job['company_name'] . ' — IMatchBetter';
require __DIR__ . '/includes/header.php';
?>
<main class="container" style="padding: 2.5rem 0; max-width: 820px;">
    <a href="<?= h(base_url('jobs.php')) ?>">&larr; Back to all jobs</a>

    <div class="card" style="margin-top:1rem;">
        <h1><?= h($job['title']) ?></h1>
        <p class="job-card-company">
            <?= h($job['company_name']) ?>
            <?php if ($reviewStats['review_count'] > 0): ?>
                <span class="star-rating"><?= str_repeat('★', (int) round($reviewStats['avg_rating'])) . str_repeat('☆', 5 - (int) round($reviewStats['avg_rating'])) ?></span>
                <span style="font-size:0.85rem;">(<?= (int) $reviewStats['review_count'] ?> review<?= (int) $reviewStats['review_count'] === 1 ? '' : 's' ?>)</span>
            <?php endif; ?>
        </p>
        <div class="job-card-meta" style="margin-bottom:1.5rem;">
            <?php if (!empty($job['location'])): ?><span><?= h($job['location']) ?></span><?php endif; ?>
            <span><?= h($employmentLabels[$job['employment_type']] ?? $job['employment_type']) ?></span>
            <?php if (!empty($job['salary_min']) || !empty($job['salary_max'])): ?>
                <span><?= h($job['salary_currency']) ?> <?= number_format((float) $job['salary_min']) ?><?= $job['salary_max'] ? ' – ' . number_format((float) $job['salary_max']) : '' ?></span>
            <?php endif; ?>
        </div>

        <h3>Description</h3>
        <p style="white-space:pre-line;"><?= h($job['description']) ?></p>

        <?php if (!empty($job['requirements'])): ?>
            <h3>Requirements</h3>
            <p style="white-space:pre-line;"><?= h($job['requirements']) ?></p>
        <?php endif; ?>

        <?php if (!empty($jobSkills['required']) || !empty($jobSkills['preferred'])): ?>
            <h3>Skills</h3>
            <div class="job-card-meta" style="margin-bottom:1rem;">
                <?php foreach ($jobSkills['required'] as $skillName): ?>
                    <span class="badge badge-open"><?= h($skillName) ?></span>
                <?php endforeach; ?>
                <?php foreach ($jobSkills['preferred'] as $skillName): ?>
                    <span class="badge badge-interview"><?= h($skillName) ?> (preferred)</span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!Auth::check()): ?>
            <a href="<?= h(base_url('auth.php?tab=login')) ?>" class="btn btn-primary">Log in to Apply</a>
        <?php elseif (Auth::role() !== 'applicant'): ?>
            <p class="form-hint">Only applicant accounts can apply to jobs.</p>
        <?php elseif ($alreadyApplied): ?>
            <p class="flash flash-info" style="display:inline-block;">You've already applied to this job.</p>
        <?php else: ?>
            <a href="<?= h(base_url('applicant/apply.php?job_id=' . $job['id'])) ?>" class="btn btn-primary">Apply Now</a>
        <?php endif; ?>

        <?php if ($isApplicant): ?>
            <form method="post" action="<?= h(base_url('applicant/save-job.php')) ?>" style="display:inline-block; margin-left:0.5rem;">
                <?= Csrf::field() ?>
                <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                <input type="hidden" name="action" value="<?= $isJobSaved ? 'unsave' : 'save' ?>">
                <input type="hidden" name="redirect" value="<?= h($_SERVER['REQUEST_URI'] ?? '') ?>">
                <button type="submit" class="btn btn-outline" aria-pressed="<?= $isJobSaved ? 'true' : 'false' ?>"><?= $isJobSaved ? '★ Saved' : '☆ Save for later' ?></button>
            </form>
        <?php endif; ?>

        <?php if (Auth::check()): ?>
            <p style="margin-top:1rem;">
                <a href="<?= h(base_url('complaints/create.php?against_type=job&against_id=' . $job['id'])) ?>" class="form-hint">Report this listing</a>
            </p>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

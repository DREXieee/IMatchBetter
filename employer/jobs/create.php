<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Job;
use IMatchBetter\Models\JobMatchQueue;
use IMatchBetter\Models\Skill;

Guard::requireApproved();

$job = [
    'title' => '', 'description' => '', 'requirements' => '', 'location' => '',
    'employment_type' => 'full_time', 'salary_min' => '', 'salary_max' => '', 'category' => '', 'status' => 'draft',
    'offers_training' => false, 'career_growth_notes' => '', 'required_skills' => '', 'preferred_skills' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    $job = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'requirements' => trim($_POST['requirements'] ?? ''),
        'location' => trim($_POST['location'] ?? ''),
        'employment_type' => $_POST['employment_type'] ?? 'full_time',
        'salary_min' => $_POST['salary_min'] ?? '',
        'salary_max' => $_POST['salary_max'] ?? '',
        'category' => trim($_POST['category'] ?? ''),
        'offers_training' => isset($_POST['offers_training']),
        'career_growth_notes' => trim($_POST['career_growth_notes'] ?? ''),
        'required_skills' => trim($_POST['required_skills'] ?? ''),
        'preferred_skills' => trim($_POST['preferred_skills'] ?? ''),
        'status' => in_array($_POST['status'] ?? '', ['draft', 'open'], true) ? $_POST['status'] : 'draft',
    ];

    if ($job['title'] === '') {
        $errors['title'] = 'Job title is required.';
    }
    if ($job['description'] === '') {
        $errors['description'] = 'Job description is required.';
    }

    if (empty($errors)) {
        $job['employer_id'] = Auth::id();
        $jobId = Job::create($job);
        Skill::syncJobSkills($jobId, Skill::parseList($job['required_skills']), Skill::parseList($job['preferred_skills']));

        if ($job['status'] === 'open') {
            // Scoring every applicant against this job happens off the request path — see
            // scripts/process-job-match-queue.php — so publishing a job stays fast regardless
            // of applicant pool size.
            JobMatchQueue::enqueue($jobId);
        }

        flash('success', 'Job posting created.');
        redirect('/employer/jobs/index.php');
    }
}

$role = 'employer';
$pageTitle = 'Post a Job — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>Post a Job</h1>
        <?php if (!empty($errors)): ?>
            <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= h(base_url('employer/jobs/create.php')) ?>" class="card" style="max-width:720px;">
            <?= Csrf::field() ?>
            <?php require __DIR__ . '/../../includes/partials/job-form-fields.php'; ?>
            <button type="submit" class="btn btn-primary">Save Job</button>
        </form>
    </main>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

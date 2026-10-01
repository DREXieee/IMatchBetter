<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Job;
use IMatchBetter\Models\Notification;
use IMatchBetter\Models\Skill;

Guard::requireApproved();

$id = (int) ($_GET['id'] ?? 0);
$job = Job::find($id);

if (!$job || !Job::isOwnedBy($id, (int) Auth::id())) {
    http_response_code(404);
    exit('Job not found.');
}

$existingSkills = Skill::namesForJob($id);
$job['required_skills'] = implode(', ', $existingSkills['required']);
$job['preferred_skills'] = implode(', ', $existingSkills['preferred']);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    $wasAlreadyApproved = $job['approval_status'] === 'approved';
    $wantsOpen = ($_POST['status'] ?? '') === 'open';

    $job = array_merge($job, [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'requirements' => trim($_POST['requirements'] ?? ''),
        'employment_process' => trim($_POST['employment_process'] ?? ''),
        'scheduling_process' => trim($_POST['scheduling_process'] ?? ''),
        'location' => trim($_POST['location'] ?? ''),
        'employment_type' => $_POST['employment_type'] ?? 'full_time',
        'salary_min' => $_POST['salary_min'] ?? '',
        'salary_max' => $_POST['salary_max'] ?? '',
        'category' => trim($_POST['category'] ?? ''),
        'offers_training' => isset($_POST['offers_training']),
        'career_growth_notes' => trim($_POST['career_growth_notes'] ?? ''),
        'required_skills' => trim($_POST['required_skills'] ?? ''),
        'preferred_skills' => trim($_POST['preferred_skills'] ?? ''),
        'status' => in_array($_POST['status'] ?? '', ['draft', 'open', 'closed'], true) ? $_POST['status'] : $job['status'],
    ]);

    if ($job['title'] === '') {
        $errors['title'] = 'Job title is required.';
    }
    if ($job['description'] === '') {
        $errors['description'] = 'Job description is required.';
    }

    if (empty($errors)) {
        Job::update($id, $job);
        Skill::syncJobSkills($id, Skill::parseList($job['required_skills']), Skill::parseList($job['preferred_skills']));

        if ($wantsOpen && !$wasAlreadyApproved) {
            foreach (Notification::adminUserIds() as $adminId) {
                Notification::create((int) $adminId, 'job_submitted', "New job posting \"{$job['title']}\" needs approval.", $id, 'job');
            }
            flash('success', 'Job submitted for admin approval. It will appear publicly once approved.');
        } else {
            flash('success', 'Job posting updated.');
        }

        redirect('/employer/jobs/index.php');
    }
}

$role = 'employer';
$pageTitle = 'Edit Job — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-shell">
    <main class="dashboard-main">
        <h1>Edit Job</h1>
        <?php if (!empty($errors)): ?>
            <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
        <?php endif; ?>
        <?php if ($job['approval_status'] === 'pending'): ?>
            <div class="flash flash-info">This job is awaiting admin approval and isn't visible to applicants yet.</div>
        <?php elseif ($job['approval_status'] === 'rejected'): ?>
            <div class="flash flash-error">This job wasn't approved<?= !empty($job['rejection_reason']) ? ': ' . h($job['rejection_reason']) : '.' ?> Set status to "Publish" again to resubmit for review.</div>
        <?php endif; ?>
        <form method="post" action="<?= h(base_url('employer/jobs/edit.php?id=' . $id)) ?>" class="card" style="max-width:720px;">
            <?= Csrf::field() ?>
            <?php require __DIR__ . '/../../includes/partials/job-form-fields.php'; ?>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </main>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

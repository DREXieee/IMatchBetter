<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Application;
use IMatchBetter\Models\Job;
use IMatchBetter\Services\SkillMatchService;

Guard::requireRole('employer');

$jobId = (int) ($_GET['job_id'] ?? 0);
$job = Job::find($jobId);

if (!$job || !Job::isOwnedBy($jobId, (int) Auth::id())) {
    http_response_code(404);
    exit('Job not found.');
}

$applications = Application::forJob($jobId);

$matchScores = [];
foreach (SkillMatchService::matchedApplicantsForJob($jobId) as $matched) {
    $matchScores[(int) $matched['application_id']] = $matched['match_score'];
}

usort($applications, static fn (array $a, array $b): int => ($matchScores[(int) $b['id']] ?? 0) <=> ($matchScores[(int) $a['id']] ?? 0));

$role = 'employer';
$pageTitle = 'Applicants — ' . $job['title'] . ' — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>Applicants for <?= h($job['title']) ?></h1>
        <p><a href="<?= h(base_url('employer/jobs/index.php')) ?>">&larr; Back to my jobs</a></p>

        <?php if (empty($applications)): ?>
            <div class="card empty-state">No applications yet.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Applicant</th><th>Email</th><th>Match</th><th>Applied</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?= h($app['applicant_name']) ?></td>
                            <td><?= h($app['applicant_email']) ?></td>
                            <td><?= (int) ($matchScores[(int) $app['id']] ?? 0) ?>%</td>
                            <td><?= h(date('M j, Y', strtotime($app['applied_at']))) ?></td>
                            <td><?php $status = $app['status']; require __DIR__ . '/../../includes/partials/status-badge.php'; ?></td>
                            <td><a href="<?= h(base_url('employer/applicants/view.php?application_id=' . $app['id'])) ?>">Review</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

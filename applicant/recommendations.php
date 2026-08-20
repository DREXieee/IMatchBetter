<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\SavedJob;
use IMatchBetter\Services\SkillMatchService;

Guard::requireRole('applicant');

$jobs = SkillMatchService::recommendedJobsForApplicant((int) Auth::id(), 20);
$savedJobIds = SavedJob::savedJobIdSet((int) Auth::id());

$role = 'applicant';
$pageTitle = 'Recommended Jobs — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>Recommended Jobs</h1>
        <p>Based on your skills and job preferences.</p>

        <?php if (empty($jobs)): ?>
            <div class="card empty-state">
                No recommendations yet. <a href="<?= h(base_url('applicant/profile.php')) ?>">Add your skills</a> to see personalized matches.
            </div>
        <?php else: ?>
            <div class="grid grid-2">
                <?php foreach ($jobs as $job): ?>
                    <div style="position:relative;">
                        <span class="badge badge-approved" style="position:absolute; top:0.75rem; right:0.75rem; z-index:1;"><?= (int) $job['match_score'] ?>% match</span>
                        <?php require __DIR__ . '/../includes/partials/job-card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

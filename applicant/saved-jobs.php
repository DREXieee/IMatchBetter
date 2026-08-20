<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\SavedJob;

Guard::requireRole('applicant');

$jobs = SavedJob::forApplicant((int) Auth::id());
$savedJobIds = array_fill_keys(array_map(static fn (array $job): int => (int) $job['id'], $jobs), true);

$role = 'applicant';
$pageTitle = 'Saved Jobs — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>Saved Jobs</h1>
        <p>Roles you've bookmarked to come back to later.</p>

        <?php if (empty($jobs)): ?>
            <div class="card empty-state">You haven't saved any jobs yet. <a href="<?= h(base_url('jobs.php')) ?>">Browse open jobs.</a></div>
        <?php else: ?>
            <div class="grid grid-2">
                <?php foreach ($jobs as $job): ?>
                    <?php require __DIR__ . '/../includes/partials/job-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

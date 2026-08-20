<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\SavedJob;
use IMatchBetter\Services\JobSearchService;

Guard::requireRole('applicant');

$page = max(1, (int) ($_GET['page'] ?? 1));
$results = JobSearchService::search(['careerGrowth' => true, 'page' => $page]);
$savedJobIds = SavedJob::savedJobIdSet((int) Auth::id());

$role = 'applicant';
$pageTitle = 'Career Growth Insights — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>Career Growth Insights</h1>
        <p>Open roles that highlight training, promotions, and long-term career development.</p>

        <?php if (empty($results['jobs'])): ?>
            <div class="card empty-state">No career-growth-flagged jobs right now. Check back soon.</div>
        <?php else: ?>
            <div class="grid grid-2">
                <?php foreach ($results['jobs'] as $job): ?>
                    <div>
                        <?php require __DIR__ . '/../includes/partials/job-card.php'; ?>
                        <?php if (!empty($job['career_growth_notes'])): ?>
                            <p class="form-hint" style="margin-top:0.5rem;"><?= h($job['career_growth_notes']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($results['totalPages'] > 1): ?>
                <div style="display:flex; justify-content:center; gap:0.5rem; margin-top:2rem;">
                    <?php for ($p = 1; $p <= $results['totalPages']; $p++): ?>
                        <a href="<?= h(base_url('applicant/career-growth.php?page=' . $p)) ?>" class="btn <?= $p === $results['page'] ? 'btn-primary' : 'btn-outline' ?>" style="min-width:44px; padding:0.6rem;"><?= $p ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

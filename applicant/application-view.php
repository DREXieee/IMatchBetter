<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Application;

Guard::requireRole('applicant');

$id = (int) ($_GET['id'] ?? 0);
$application = Application::findWithContext($id);

if (!$application || (int) $application['applicant_id'] !== (int) Auth::id()) {
    http_response_code(404);
    exit('Application not found.');
}

$statusSteps = ['submitted', 'reviewed', 'interview', 'hired'];
$currentIndex = array_search($application['status'], $statusSteps, true);
$isRejected = $application['status'] === 'rejected';

$role = 'applicant';
$pageTitle = 'Application — ' . $application['job_title'] . ' — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1><?= h($application['job_title']) ?></h1>

        <div class="card" style="max-width:640px;">
            <p><strong>Status:</strong>
                <?php $status = $application['status']; require __DIR__ . '/../includes/partials/status-badge.php'; ?>
            </p>

            <?php if (!$isRejected): ?>
                <div style="display:flex; gap:0.5rem; margin: 1rem 0;">
                    <?php foreach ($statusSteps as $i => $step): ?>
                        <div style="flex:1; text-align:center;">
                            <div style="height:6px; border-radius:3px; background: <?= $i <= $currentIndex ? 'var(--color-primary)' : 'var(--color-border)' ?>;"></div>
                            <span style="font-size:0.75rem; color: var(--color-text-muted); text-transform:capitalize;"><?= h($step) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p><strong>Applied:</strong> <?= h(date('M j, Y g:i A', strtotime($application['applied_at']))) ?></p>
            <?php if (!empty($application['status_updated_at'])): ?>
                <p><strong>Last updated:</strong> <?= h(date('M j, Y g:i A', strtotime($application['status_updated_at']))) ?></p>
            <?php endif; ?>

            <?php if (!empty($application['cover_letter'])): ?>
                <h3>Your cover letter</h3>
                <p style="white-space:pre-line;"><?= h($application['cover_letter']) ?></p>
            <?php endif; ?>

            <p><a href="<?= h(base_url('download.php?resume_id=' . $application['resume_id'])) ?>">Download submitted resume</a></p>

            <p><a href="<?= h(base_url('complaints/create.php?against_type=application&against_id=' . $application['id'])) ?>" class="form-hint">Report this employer</a></p>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Job;

Guard::requireRole('admin');

$pending = Job::pendingApproval(100, 0);

$role = 'admin';
$pageTitle = 'Job Approvals — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-shell">
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Job Approvals</h1>
            <p>Review new job postings before they go live.</p>
        </div>

        <?php if (empty($pending)): ?>
            <div class="card empty-state">No jobs awaiting approval.</div>
        <?php else: ?>
            <?php foreach ($pending as $job): ?>
                <div class="card" style="margin-bottom:1rem;">
                    <h3><?= h($job['title']) ?></h3>
                    <p class="form-hint"><?= h($job['company_name']) ?> · Requested <?= h(date('M j, Y', strtotime($job['approval_requested_at']))) ?></p>
                    <p style="white-space:pre-line;"><?= h($job['description']) ?></p>
                    <?php if (!empty($job['requirements'])): ?><p><strong>Requirements:</strong> <?= h($job['requirements']) ?></p><?php endif; ?>
                    <?php if (!empty($job['employment_process'])): ?><p><strong>Employment process:</strong> <?= h($job['employment_process']) ?></p><?php endif; ?>
                    <?php if (!empty($job['scheduling_process'])): ?><p><strong>Scheduling process:</strong> <?= h($job['scheduling_process']) ?></p><?php endif; ?>
                    <div style="display:flex; gap:0.5rem; margin-top:1rem;">
                        <form method="post" action="<?= h(base_url('admin/jobs/approve.php')) ?>">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $job['id'] ?>">
                            <button type="submit" class="btn btn-primary">Approve</button>
                        </form>
                        <form method="post" action="<?= h(base_url('admin/jobs/reject.php')) ?>" onsubmit="return collectReason(this);">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $job['id'] ?>">
                            <input type="hidden" name="reason" value="">
                            <button type="submit" class="btn btn-outline">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
<script>
function collectReason(form) {
    var reason = prompt('Reason for rejection (shown to the employer):');
    if (reason === null || reason.trim() === '') {
        return false;
    }
    form.querySelector('input[name="reason"]').value = reason.trim();
    return true;
}
</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

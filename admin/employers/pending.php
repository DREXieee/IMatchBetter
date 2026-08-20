<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\EmployerProfile;

Guard::requireRole('admin');

$pending = EmployerProfile::pending(100, 0);

$role = 'admin';
$pageTitle = 'Employer Approvals — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Employer Approvals</h1>
            <p>Review pending employer registrations before they can post jobs.</p>
        </div>

        <?php if (empty($pending)): ?>
            <div class="card empty-state">No pending employer requests right now.</div>
        <?php else: ?>
            <?php foreach ($pending as $ep): ?>
                <div class="card" style="margin-bottom: 1rem;">
                    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                        <div>
                            <h3><?= h($ep['company_name']) ?></h3>
                            <p style="margin-bottom:0.25rem;"><strong><?= h($ep['full_name']) ?></strong> — <?= h($ep['email']) ?></p>
                            <?php if (!empty($ep['company_website'])): ?>
                                <p style="margin-bottom:0.25rem;"><a href="<?= h($ep['company_website']) ?>" target="_blank" rel="noopener">Visit website</a></p>
                            <?php endif; ?>
                            <?php if (!empty($ep['company_description'])): ?>
                                <p><?= h($ep['company_description']) ?></p>
                            <?php endif; ?>
                            <p class="form-hint">Requested <?= h(date('M j, Y', strtotime($ep['created_at']))) ?></p>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:0.5rem; min-width:160px;">
                            <form method="post" action="<?= h(base_url('admin/employers/approve.php')) ?>">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $ep['id'] ?>">
                                <button type="submit" class="btn btn-primary btn-block">Approve</button>
                            </form>
                            <form method="post" action="<?= h(base_url('admin/employers/reject.php')) ?>" onsubmit="return collectReason(this);">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $ep['id'] ?>">
                                <input type="hidden" name="reason" value="">
                                <button type="submit" class="btn btn-outline btn-block">Reject</button>
                            </form>
                        </div>
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

<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Interview;

Guard::requireRole('applicant');

$interviews = Interview::forApplicant((int) Auth::id());

$role = 'applicant';
$pageTitle = 'Interviews — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>Interviews</h1>

        <?php if (empty($interviews)): ?>
            <div class="card empty-state">No interviews scheduled yet.</div>
        <?php else: ?>
            <?php foreach ($interviews as $interview): ?>
                <div class="card" style="margin-bottom:1rem; max-width:640px;">
                    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                        <div>
                            <h3><?= h($interview['job_title']) ?></h3>
                            <p style="margin-bottom:0.25rem;"><?= h($interview['company_name']) ?></p>
                            <p style="margin-bottom:0.25rem;"><strong><?= h(date('M j, Y g:i A', strtotime($interview['scheduled_at']))) ?></strong> &middot; <?= h(ucfirst($interview['mode'])) ?></p>
                            <?php if (!empty($interview['location_or_link'])): ?>
                                <p style="margin-bottom:0.25rem;"><?= h($interview['location_or_link']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($interview['notes'])): ?>
                                <p class="form-hint"><?= h($interview['notes']) ?></p>
                            <?php endif; ?>
                            <p><?php $status = $interview['status']; require __DIR__ . '/../../includes/partials/status-badge.php'; ?></p>
                        </div>
                        <?php if ($interview['status'] === 'proposed' || $interview['status'] === 'rescheduled'): ?>
                            <div style="display:flex; flex-direction:column; gap:0.5rem; min-width:160px;">
                                <form method="post" action="<?= h(base_url('applicant/interviews/respond.php')) ?>">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $interview['id'] ?>">
                                    <input type="hidden" name="response" value="confirmed">
                                    <button type="submit" class="btn btn-primary btn-block">Confirm</button>
                                </form>
                                <form method="post" action="<?= h(base_url('applicant/interviews/respond.php')) ?>">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $interview['id'] ?>">
                                    <input type="hidden" name="response" value="declined">
                                    <button type="submit" class="btn btn-outline btn-block">Decline</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

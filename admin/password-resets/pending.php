<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\PasswordResetRequest;

Guard::requireRole('admin');

$pending = PasswordResetRequest::pending(100, 0);

$role = 'admin';
$pageTitle = 'Password Reset Requests — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-shell">
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Password Reset Requests</h1>
            <p>Approve a request to email the user a reset link; reject to discard it.</p>
        </div>

        <?php if (empty($pending)): ?>
            <div class="card empty-state">No pending password reset requests.</div>
        <?php else: ?>
            <?php foreach ($pending as $req): ?>
                <div class="card" style="margin-bottom:1rem;">
                    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                        <div>
                            <p><strong><?= h($req['full_name']) ?></strong> — <?= h($req['email']) ?></p>
                            <p class="form-hint">Requested <?= h(date('M j, Y g:i A', strtotime($req['created_at']))) ?></p>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:0.5rem; min-width:160px;">
                            <form method="post" action="<?= h(base_url('admin/password-resets/approve.php')) ?>">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $req['id'] ?>">
                                <button type="submit" class="btn btn-primary btn-block">Approve</button>
                            </form>
                            <form method="post" action="<?= h(base_url('admin/password-resets/reject.php')) ?>" onsubmit="return confirm('Reject this password reset request?');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $req['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-block">Reject</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

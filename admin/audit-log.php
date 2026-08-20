<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Guard;
use IMatchBetter\Services\AuditLogger;

Guard::requireRole('admin');

$actions = AuditLogger::recent(200);

$role = 'admin';
$pageTitle = 'Audit Log — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>Audit Log</h1>

        <?php if (empty($actions)): ?>
            <div class="card empty-state">No admin actions recorded yet.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Target</th><th>Notes</th></tr></thead>
                    <tbody>
                    <?php foreach ($actions as $a): ?>
                        <tr>
                            <td><?= h(date('M j, Y g:i A', strtotime($a['created_at']))) ?></td>
                            <td><?= h($a['admin_name']) ?></td>
                            <td><?= h(str_replace('_', ' ', $a['action_type'])) ?></td>
                            <td><?= h($a['target_type']) ?> #<?= (int) $a['target_id'] ?></td>
                            <td><?= h($a['notes'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Complaint;

Guard::requireRole('employer');

$complaints = Complaint::forUser((int) Auth::id());

$role = 'employer';
$pageTitle = 'My Complaints — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1rem;">
            <h1 style="margin:0;">My Complaints</h1>
            <a href="<?= h(base_url('complaints/create.php')) ?>" class="btn btn-primary">File a Complaint</a>
        </div>

        <?php if (empty($complaints)): ?>
            <div class="card empty-state">You haven't filed any complaints.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Category</th><th>Message</th><th>Filed</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($complaints as $c): ?>
                        <tr>
                            <td><?= h(str_replace('_', ' ', $c['category'])) ?></td>
                            <td><?= h($c['message']) ?></td>
                            <td><?= h(date('M j, Y', strtotime($c['created_at']))) ?></td>
                            <td><span class="badge badge-complaint-<?= h($c['status']) ?>"><?= h($c['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

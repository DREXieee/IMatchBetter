<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Complaint;

Guard::requireRole('admin');

$statusFilter = $_GET['status'] ?? '';
$validStatuses = ['open', 'investigating', 'resolved', 'dismissed'];
$page = (int) ($_GET['page'] ?? 1);
$results = Complaint::all(in_array($statusFilter, $validStatuses, true) ? $statusFilter : null, $page);
$complaints = $results['complaints'];

$role = 'admin';
$pageTitle = 'Complaints — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Complaints</h1>
            <p>Review and resolve complaints filed by users.</p>
        </div>

        <div style="display:flex; gap:0.5rem; margin-bottom:1rem; flex-wrap:wrap;">
            <a href="<?= h(base_url('admin/complaints/index.php')) ?>" class="btn <?= $statusFilter === '' ? 'btn-primary' : 'btn-outline' ?>">All</a>
            <?php foreach ($validStatuses as $s): ?>
                <a href="<?= h(base_url('admin/complaints/index.php?status=' . $s)) ?>" class="btn <?= $statusFilter === $s ? 'btn-primary' : 'btn-outline' ?>"><?= h(ucfirst($s)) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($complaints)): ?>
            <div class="card empty-state">No complaints found.</div>
        <?php else: ?>
            <?php foreach ($complaints as $c): ?>
                <div class="card" style="margin-bottom:1rem;">
                    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                        <div>
                            <p style="margin-bottom:0.25rem;"><strong><?= h(str_replace('_', ' ', $c['category'])) ?></strong> — <?= h(Complaint::describeTarget($c['against_type'], (int) $c['against_id']) ?? ($c['against_type'] . ' #' . $c['against_id'] . ' (no longer exists)')) ?></p>
                            <p style="margin-bottom:0.25rem;">Filed by <?= h($c['complainant_name']) ?> (<?= h($c['complainant_email']) ?>) on <?= h(date('M j, Y', strtotime($c['created_at']))) ?></p>
                            <p><?= h($c['message']) ?></p>
                            <?php if (!empty($c['resolution_notes'])): ?>
                                <p class="form-hint">Resolution: <?= h($c['resolution_notes']) ?></p>
                            <?php endif; ?>
                            <span class="badge badge-complaint-<?= h($c['status']) ?>"><?= h($c['status']) ?></span>
                        </div>
                        <?php if (!in_array($c['status'], ['resolved', 'dismissed'], true)): ?>
                            <form method="post" action="<?= h(base_url('admin/complaints/update-status.php')) ?>" style="min-width:220px;">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <div class="form-group">
                                    <select class="form-control" name="status">
                                        <option value="investigating" <?= $c['status'] === 'investigating' ? 'selected' : '' ?>>Investigating</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="dismissed">Dismissed</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <textarea class="form-control" name="resolution_notes" rows="2" placeholder="Resolution notes (optional)"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Update</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($results['totalPages'] > 1): ?>
                <div style="display:flex; justify-content:center; gap:0.5rem; margin-top:2rem;">
                    <?php for ($p = 1; $p <= $results['totalPages']; $p++): ?>
                        <?php $query = array_merge($statusFilter !== '' ? ['status' => $statusFilter] : [], ['page' => $p]); ?>
                        <a href="<?= h(base_url('admin/complaints/index.php?' . http_build_query($query))) ?>" class="btn <?= $p === $results['page'] ? 'btn-primary' : 'btn-outline' ?>" style="min-width:44px; padding:0.6rem;"><?= $p ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

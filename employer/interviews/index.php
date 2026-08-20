<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Interview;

Guard::requireRole('employer');

$interviews = Interview::forEmployer((int) Auth::id());

$role = 'employer';
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
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Applicant</th><th>Job</th><th>Scheduled</th><th>Mode</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($interviews as $interview): ?>
                        <tr>
                            <td><?= h($interview['applicant_name']) ?></td>
                            <td><?= h($interview['job_title']) ?></td>
                            <td><?= h(date('M j, Y g:i A', strtotime($interview['scheduled_at']))) ?></td>
                            <td><?= h(ucfirst($interview['mode'])) ?></td>
                            <td><?php $status = $interview['status']; require __DIR__ . '/../../includes/partials/status-badge.php'; ?></td>
                            <td><a href="<?= h(base_url('employer/applicants/view.php?id=' . $interview['application_id'])) ?>">View application</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

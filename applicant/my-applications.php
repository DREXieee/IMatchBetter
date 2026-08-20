<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Application;

Guard::requireRole('applicant');

$applications = Application::forApplicant((int) Auth::id());

$role = 'applicant';
$pageTitle = 'My Applications — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>My Applications</h1>

        <?php if (empty($applications)): ?>
            <div class="card empty-state">You haven't applied to any jobs yet. <a href="<?= h(base_url('jobs.php')) ?>">Browse open jobs.</a></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Job</th><th>Company</th><th>Applied</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?= h($app['job_title']) ?></td>
                            <td><?= h($app['company_name']) ?></td>
                            <td><?= h(date('M j, Y', strtotime($app['applied_at']))) ?></td>
                            <td><?php $status = $app['status']; require __DIR__ . '/../includes/partials/status-badge.php'; ?></td>
                            <td><a href="<?= h(base_url('applicant/application-view.php?id=' . $app['id'])) ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

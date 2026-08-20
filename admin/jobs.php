<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Job;
use IMatchBetter\Services\AuditLogger;

Guard::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    $jobId = (int) ($_POST['id'] ?? 0);
    $job = Job::find($jobId);

    if ($job) {
        Job::adminRemove($jobId);
        AuditLogger::log((int) Auth::id(), 'job_removed', 'job', $jobId, $job['title']);
        flash('success', 'Job removed.');
    }

    redirect('/admin/jobs.php');
}

$jobs = Job::adminList(200, 0);

$role = 'admin';
$pageTitle = 'Moderate Jobs — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>Moderate Jobs</h1>

        <?php if (empty($jobs)): ?>
            <div class="card empty-state">No jobs posted yet.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Title</th><th>Company</th><th>Status</th><th>Posted</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td><a href="<?= h(base_url('job-view.php?slug=' . urlencode($job['slug']))) ?>" target="_blank" rel="noopener"><?= h($job['title']) ?></a></td>
                            <td><?= h($job['company_name']) ?></td>
                            <td><span class="badge badge-<?= h($job['status']) ?>"><?= h($job['status']) ?></span></td>
                            <td><?= $job['posted_at'] ? h(date('M j, Y', strtotime($job['posted_at']))) : '—' ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Remove this job posting permanently?');">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $job['id'] ?>">
                                    <button type="submit" class="btn btn-outline" style="padding:0.2rem 0.6rem; min-height:auto;">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

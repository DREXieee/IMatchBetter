<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Application;
use IMatchBetter\Models\Job;

Guard::requireRole('employer');

$jobs = Job::forEmployer((int) Auth::id());
$applicantCounts = Application::countsByEmployer((int) Auth::id());
$funnelLabels = ['submitted' => 'Submitted', 'reviewed' => 'Reviewed', 'interview' => 'Interview', 'rejected' => 'Rejected', 'hired' => 'Hired'];

$role = 'employer';
$pageTitle = 'My Job Postings — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
            <div>
                <h1>My Job Postings</h1>
                <p>Manage the roles you've posted.</p>
            </div>
            <a href="<?= h(base_url('employer/jobs/create.php')) ?>" class="btn btn-primary">+ Post a Job</a>
        </div>

        <?php if (empty($jobs)): ?>
            <div class="card empty-state">You haven't posted any jobs yet.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Title</th><th>Status</th><th>Posted</th><th>Applicants</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <?php $counts = $applicantCounts[$job['id']] ?? ['total' => 0]; ?>
                        <tr>
                            <td><?= h($job['title']) ?></td>
                            <td><span class="badge badge-<?= h($job['status']) ?>"><?= h($job['status']) ?></span></td>
                            <td><?= $job['posted_at'] ? h(date('M j, Y', strtotime($job['posted_at']))) : '—' ?></td>
                            <td>
                                <a href="<?= h(base_url('employer/applicants/index.php?job_id=' . $job['id'])) ?>"><?= $counts['total'] ?> total</a>
                                <?php if ($counts['total'] > 0): ?>
                                    <div class="form-hint">
                                        <?php
                                        $parts = [];
                                        foreach ($funnelLabels as $status => $label) {
                                            if (!empty($counts[$status])) {
                                                $parts[] = $counts[$status] . ' ' . $label;
                                            }
                                        }
                                        echo h(implode(' · ', $parts));
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= h(base_url('employer/jobs/edit.php?id=' . $job['id'])) ?>">Edit</a>
                                &nbsp;|&nbsp;
                                <a href="<?= h(base_url('employer/applicants/index.php?job_id=' . $job['id'])) ?>">Applicants</a>
                                <?php if ($job['status'] !== 'closed'): ?>
                                    &nbsp;|&nbsp;
                                    <form method="post" action="<?= h(base_url('employer/jobs/close.php')) ?>" style="display:inline;" onsubmit="return confirm('Close this job posting?');">
                                        <?= \IMatchBetter\Auth\Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $job['id'] ?>">
                                        <button type="submit" class="btn btn-outline" style="padding:0.2rem 0.6rem; min-height:auto;">Close</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Config\Database;

Guard::requireRole('admin');

$pdo = Database::connection();
$pendingEmployers = (int) $pdo->query("SELECT COUNT(*) FROM employer_profiles WHERE approval_status = 'pending'")->fetchColumn();
$totalJobs = (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'open'")->fetchColumn();
$totalApplications = (int) $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$openComplaints = (int) $pdo->query("SELECT COUNT(*) FROM complaints WHERE status IN ('open', 'investigating')")->fetchColumn();
$pendingReviews = (int) $pdo->query(
    "SELECT (SELECT COUNT(*) FROM employer_reviews WHERE status = 'pending')
           + (SELECT COUNT(*) FROM applicant_reviews WHERE status = 'pending')"
)->fetchColumn();

$role = 'admin';
$pageTitle = 'Admin Dashboard — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Admin Overview</h1>
            <p>Welcome, <?= h(Auth::fullName()) ?></p>
        </div>

        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-value"><?= $pendingEmployers ?></div>
                <div class="stat-label">Pending Employer Approvals</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $totalJobs ?></div>
                <div class="stat-label">Open Jobs</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $totalApplications ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $totalUsers ?></div>
                <div class="stat-label">Active Users</div>
            </div>
        </div>

        <?php if ($pendingEmployers > 0): ?>
            <div class="card" style="margin-bottom:1rem;">
                <h3><?= $pendingEmployers ?> employer<?= $pendingEmployers === 1 ? '' : 's' ?> waiting for review</h3>
                <a href="<?= h(base_url('admin/employers/pending.php')) ?>" class="btn btn-primary">Review now</a>
            </div>
        <?php endif; ?>

        <div class="grid grid-3">
            <a href="<?= h(base_url('admin/complaints/index.php')) ?>" class="card" style="text-decoration:none;">
                <h3>Complaints</h3>
                <p><?= $openComplaints ?> open complaint<?= $openComplaints === 1 ? '' : 's' ?></p>
            </a>
            <a href="<?= h(base_url('admin/reviews/index.php')) ?>" class="card" style="text-decoration:none;">
                <h3>Review Moderation</h3>
                <p><?= $pendingReviews ?> review<?= $pendingReviews === 1 ? '' : 's' ?> awaiting approval</p>
            </a>
            <a href="<?= h(base_url('admin/reports.php')) ?>" class="card" style="text-decoration:none;">
                <h3>Reports &amp; Insights</h3>
                <p>Platform-wide activity breakdown.</p>
            </a>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

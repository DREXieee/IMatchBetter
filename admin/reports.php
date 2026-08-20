<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Guard;
use IMatchBetter\Config\Database;

Guard::requireRole('admin');

$pdo = Database::connection();

$usersByRole = $pdo->query("SELECT role, COUNT(*) AS total FROM users GROUP BY role")->fetchAll();
$jobsByStatus = $pdo->query("SELECT status, COUNT(*) AS total FROM jobs GROUP BY status")->fetchAll();
$applicationsByStatus = $pdo->query("SELECT status, COUNT(*) AS total FROM applications GROUP BY status")->fetchAll();

$openComplaints = (int) $pdo->query("SELECT COUNT(*) FROM complaints WHERE status IN ('open', 'investigating')")->fetchColumn();
$pendingReviews = (int) $pdo->query(
    "SELECT (SELECT COUNT(*) FROM employer_reviews WHERE status = 'pending')
           + (SELECT COUNT(*) FROM applicant_reviews WHERE status = 'pending')"
)->fetchColumn();
$avgEmployerRating = (float) $pdo->query("SELECT COALESCE(AVG(rating), 0) FROM employer_reviews WHERE status = 'approved'")->fetchColumn();
$avgApplicantRating = (float) $pdo->query("SELECT COALESCE(AVG(rating), 0) FROM applicant_reviews WHERE status = 'approved'")->fetchColumn();
$interviewsThisWeek = (int) $pdo->query(
    "SELECT COUNT(*) FROM interviews WHERE scheduled_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)"
)->fetchColumn();

$applicationTotal = array_sum(array_column($applicationsByStatus, 'total')) ?: 1;

$role = 'admin';
$pageTitle = 'Reports & Insights — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Reports &amp; Insights</h1>
            <p>Platform-wide activity at a glance.</p>
        </div>

        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-value"><?= $openComplaints ?></div>
                <div class="stat-label">Open Complaints</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $pendingReviews ?></div>
                <div class="stat-label">Reviews Awaiting Moderation</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $interviewsThisWeek ?></div>
                <div class="stat-label">Interviews This Week</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($avgEmployerRating, 1) ?> / <?= number_format($avgApplicantRating, 1) ?></div>
                <div class="stat-label">Avg Rating (Employers / Applicants)</div>
            </div>
        </div>

        <div class="grid grid-3">
            <div class="card">
                <h3>Users by Role</h3>
                <?php foreach ($usersByRole as $row): ?>
                    <p style="display:flex; justify-content:space-between;"><span><?= h(ucfirst($row['role'])) ?></span> <strong><?= (int) $row['total'] ?></strong></p>
                <?php endforeach; ?>
            </div>
            <div class="card">
                <h3>Jobs by Status</h3>
                <?php foreach ($jobsByStatus as $row): ?>
                    <p style="display:flex; justify-content:space-between;"><span><?= h(ucfirst($row['status'])) ?></span> <strong><?= (int) $row['total'] ?></strong></p>
                <?php endforeach; ?>
            </div>
            <div class="card">
                <h3>Applications by Status</h3>
                <?php foreach ($applicationsByStatus as $row): ?>
                    <?php $pct = round(((int) $row['total'] / $applicationTotal) * 100); ?>
                    <p style="margin-bottom:0.25rem; display:flex; justify-content:space-between;"><span><?= h(ucfirst($row['status'])) ?></span> <strong><?= (int) $row['total'] ?></strong></p>
                    <div style="background:var(--color-bg-alt); border-radius:999px; height:8px; margin-bottom:0.75rem;">
                        <div style="background:var(--color-primary); border-radius:999px; height:8px; width:<?= $pct ?>%;"></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

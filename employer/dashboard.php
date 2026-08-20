<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\EmployerProfile;
use IMatchBetter\Models\Notification;

Guard::requireRole('employer');

$profile = EmployerProfile::findByUserId((int) Auth::id());
$isApproved = $profile && $profile['approval_status'] === 'approved';
$unreadCount = Notification::unreadCount((int) Auth::id());

$role = 'employer';
$pageTitle = 'Employer Dashboard — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Welcome, <?= h(Auth::fullName()) ?></h1>
            <p><?= h($profile['company_name'] ?? '') ?></p>
        </div>

        <?php if (!$isApproved): ?>
            <div class="card" style="border-color: var(--color-warning); margin-bottom:1.5rem;">
                <h3>Your account is pending approval</h3>
                <p>An admin needs to review your company before you can post jobs. You can still edit your company profile in the meantime.</p>
                <a href="<?= h(base_url('employer/pending-approval.php')) ?>" class="btn btn-outline">View status</a>
            </div>
        <?php endif; ?>

        <div class="grid grid-2">
            <a href="<?= h(base_url('employer/company-profile.php')) ?>" class="card" style="text-decoration:none;">
                <h3>Company Profile</h3>
                <p>Update your company info and logo.</p>
            </a>
            <?php if ($isApproved): ?>
            <a href="<?= h(base_url('employer/jobs/index.php')) ?>" class="card" style="text-decoration:none;">
                <h3>My Job Postings</h3>
                <p>Create, edit, and close job listings.</p>
            </a>
            <a href="<?= h(base_url('employer/talent-database.php')) ?>" class="card" style="text-decoration:none;">
                <h3>Graduate Talent Database</h3>
                <p>Search graduates and job seekers by school, degree, and skills.</p>
            </a>
            <?php else: ?>
            <div class="card" style="opacity:0.6;">
                <h3>My Job Postings</h3>
                <p>Available once your account is approved.</p>
            </div>
            <?php endif; ?>
            <a href="<?= h(base_url('employer/notifications.php')) ?>" class="card" style="text-decoration:none;">
                <h3>Notifications</h3>
                <p><?= $unreadCount > 0 ? $unreadCount . ' unread notification' . ($unreadCount === 1 ? '' : 's') : 'You\'re all caught up.' ?></p>
            </a>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

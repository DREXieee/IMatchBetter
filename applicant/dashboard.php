<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Notification;

Guard::requireRole('applicant');

$unreadCount = Notification::unreadCount((int) Auth::id());

$role = 'applicant';
$pageTitle = 'My Dashboard — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Welcome back, <?= h(Auth::fullName()) ?></h1>
            <p>Here's a quick look at your job search.</p>
        </div>

        <div class="grid grid-2">
            <a href="<?= h(base_url('applicant/profile.php')) ?>" class="card" style="text-decoration:none;">
                <h3>Complete your profile</h3>
                <p>Add your headline, skills, and upload a resume so employers can find you.</p>
            </a>
            <a href="<?= h(base_url('jobs.php')) ?>" class="card" style="text-decoration:none;">
                <h3>Browse open jobs</h3>
                <p>Search and filter listings from approved employers.</p>
            </a>
            <a href="<?= h(base_url('applicant/recommendations.php')) ?>" class="card" style="text-decoration:none;">
                <h3>Recommended for you</h3>
                <p>Jobs matched to your skills and preferences.</p>
            </a>
            <a href="<?= h(base_url('applicant/notifications.php')) ?>" class="card" style="text-decoration:none;">
                <h3>Notifications</h3>
                <p><?= $unreadCount > 0 ? $unreadCount . ' unread notification' . ($unreadCount === 1 ? '' : 's') : 'You\'re all caught up.' ?></p>
            </a>
        </div>

        <div class="card" style="margin-top:1.5rem;">
            <h3>My Applications</h3>
            <p class="empty-state">You haven't applied to any jobs yet. <a href="<?= h(base_url('jobs.php')) ?>">Find a job to apply to.</a></p>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

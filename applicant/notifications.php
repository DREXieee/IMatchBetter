<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Notification;

Guard::requireRole('applicant');

$notifications = Notification::forUser((int) Auth::id(), 50);

$role = 'applicant';
$pageTitle = 'Notifications — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <?php require __DIR__ . '/../includes/partials/notifications-list.php'; ?>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

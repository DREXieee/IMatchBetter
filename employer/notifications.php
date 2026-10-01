<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Notification;

Guard::requireRole('employer');

$notifications = Notification::forUser((int) Auth::id(), 50);

$role = 'employer';
$pageTitle = 'Notifications — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <main class="dashboard-main">
        <?php require __DIR__ . '/../includes/partials/notifications-list.php'; ?>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

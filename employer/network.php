<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Connection;

Guard::requireRole('employer');

$userId = (int) Auth::id();
$pendingIncoming = Connection::pendingIncoming($userId);
$accepted = Connection::listAccepted($userId);
$suggestions = Connection::suggestionsForUser($userId, 6);

$role = 'employer';
$pageTitle = 'Network — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <?php require __DIR__ . '/../includes/partials/network-page.php'; ?>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Message;
use IMatchBetter\Models\User;

Guard::requireRole('employer');

$userId = (int) Auth::id();
$activeCounterpartId = (int) ($_GET['with'] ?? 0);
$activeCounterpart = null;
$thread = [];

if ($activeCounterpartId > 0) {
    $activeCounterpart = User::findById($activeCounterpartId);
    if ($activeCounterpart) {
        Message::markThreadRead($userId, $activeCounterpartId);
        $thread = Message::threadBetween($userId, $activeCounterpartId);
    }
}

$conversations = Message::conversationsForUser($userId);

$role = 'employer';
$pageTitle = 'Messages — IMatchBetter';
$extraStylesheets = ['css/dashboard.css', 'css/messages.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <?php require __DIR__ . '/../includes/partials/messages-thread.php'; ?>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

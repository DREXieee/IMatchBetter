<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Notification;
use IMatchBetter\Models\NotificationBroadcast;
use IMatchBetter\Services\AuditLogger;

Guard::requireRole('admin');

$adminId = (int) Auth::id();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    $targetRole = $_POST['target_role'] ?? '';
    $message = trim($_POST['message'] ?? '');

    if (!NotificationBroadcast::isValidTargetRole($targetRole)) {
        $errors['target_role'] = 'Choose who should receive this broadcast.';
    }
    if ($message === '') {
        $errors['message'] = 'Enter a message to send.';
    } elseif (mb_strlen($message) > 500) {
        $errors['message'] = 'Message is too long (max 500 characters).';
    }

    if (empty($errors)) {
        $result = NotificationBroadcast::send($adminId, $targetRole, $message);

        $audienceLabels = ['all' => 'everyone', 'applicant' => 'job seekers', 'employer' => 'employers', 'admin' => 'admins'];
        AuditLogger::log(
            $adminId,
            'notification_broadcast',
            'notification_broadcast',
            $result['id'],
            "To {$audienceLabels[$targetRole]} ({$result['recipient_count']} recipients): {$message}"
        );

        flash('success', "Broadcast sent to {$result['recipient_count']} " . ($result['recipient_count'] === 1 ? 'user' : 'users') . '.');
        redirect('/admin/notifications.php');
    }
}

$recentBroadcasts = NotificationBroadcast::recent(10);
$notifications = Notification::forUser($adminId, 50);

$role = 'admin';
$pageTitle = 'Notifications — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <div class="card" style="margin-bottom:1.5rem;">
            <h1 style="margin-bottom:0.25rem;">Send a Broadcast</h1>
            <p class="form-hint">Push a message straight to users' notification inboxes.</p>

            <form method="post" action="<?= h(base_url('admin/notifications.php')) ?>">
                <?= Csrf::field() ?>

                <div class="form-group">
                    <label class="form-label" for="target_role">Send to</label>
                    <select class="form-control" id="target_role" name="target_role">
                        <option value="all" <?= ($_POST['target_role'] ?? '') === 'all' ? 'selected' : '' ?>>Everyone</option>
                        <option value="applicant" <?= ($_POST['target_role'] ?? '') === 'applicant' ? 'selected' : '' ?>>Job Seekers</option>
                        <option value="employer" <?= ($_POST['target_role'] ?? '') === 'employer' ? 'selected' : '' ?>>Employers</option>
                        <option value="admin" <?= ($_POST['target_role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admins</option>
                    </select>
                    <?php if (!empty($errors['target_role'])): ?><div class="form-error"><?= h($errors['target_role']) ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="message">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="3" maxlength="500"><?= h($_POST['message'] ?? '') ?></textarea>
                    <?php if (!empty($errors['message'])): ?><div class="form-error"><?= h($errors['message']) ?></div><?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Send Broadcast</button>
            </form>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <h3>Recent Broadcasts</h3>
            <?php if (empty($recentBroadcasts)): ?>
                <p class="empty-state">No broadcasts sent yet.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Sent</th><th>By</th><th>Audience</th><th>Message</th><th>Recipients</th></tr></thead>
                        <tbody>
                        <?php $audienceLabels = ['all' => 'Everyone', 'applicant' => 'Job Seekers', 'employer' => 'Employers', 'admin' => 'Admins']; ?>
                        <?php foreach ($recentBroadcasts as $broadcast): ?>
                            <tr>
                                <td><?= h(date('M j, Y g:i A', strtotime($broadcast['created_at']))) ?></td>
                                <td><?= h($broadcast['admin_name']) ?></td>
                                <td><?= h($audienceLabels[$broadcast['target_role']] ?? $broadcast['target_role']) ?></td>
                                <td><?= h($broadcast['message']) ?></td>
                                <td><?= (int) $broadcast['recipient_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <?php require __DIR__ . '/../includes/partials/notifications-list.php'; ?>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

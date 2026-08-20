<?php
/** @var array $notifications */
?>
<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1rem;">
    <h1 style="margin:0;">Notifications</h1>
    <?php if (!empty($notifications)): ?>
        <form method="post" action="<?= h(base_url('notifications/mark-all-read.php')) ?>">
            <?= \IMatchBetter\Auth\Csrf::field() ?>
            <button type="submit" class="btn btn-outline">Mark all read</button>
        </form>
    <?php endif; ?>
</div>

<?php if (empty($notifications)): ?>
    <div class="card empty-state">No notifications yet.</div>
<?php else: ?>
    <div class="card" style="padding:0;">
        <?php foreach ($notifications as $notification): ?>
            <div class="notification-item <?= $notification['is_read'] ? '' : 'is-unread' ?>">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem;">
                    <div>
                        <p style="margin:0 0 0.25rem;"><?= h($notification['message']) ?></p>
                        <p class="form-hint" style="margin:0;"><?= h(date('M j, Y g:i A', strtotime($notification['created_at']))) ?></p>
                    </div>
                    <?php if (!$notification['is_read']): ?>
                        <form method="post" action="<?= h(base_url('notifications/mark-read.php')) ?>">
                            <?= \IMatchBetter\Auth\Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $notification['id'] ?>">
                            <button type="submit" class="btn btn-outline" style="padding:0.2rem 0.6rem; min-height:auto;">Mark read</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

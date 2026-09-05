<?php
/**
 * @var int $userId
 * @var string $role
 * @var array $conversations
 * @var int $activeCounterpartId
 * @var array $thread
 * @var array|null $activeCounterpart
 */

use IMatchBetter\Auth\Csrf;

?>
<h1>Messages</h1>
<div class="messages-shell">
    <div class="messages-list">
        <div class="messages-list-header">Conversations</div>
        <?php if (empty($conversations)): ?>
            <p class="empty-state" style="padding:1rem;">No conversations yet.</p>
        <?php endif; ?>
        <?php foreach ($conversations as $conversation): ?>
            <a
                href="<?= h(base_url($role . '/messages.php?with=' . (int) $conversation['other_id'])) ?>"
                class="messages-list-item<?= (int) $conversation['other_id'] === $activeCounterpartId ? ' is-active' : '' ?>"
            >
                <p class="messages-list-item-name">
                    <?= h($conversation['other_name']) ?>
                    <?php if ((int) $conversation['unread_count'] > 0): ?><span class="messages-list-item-unread-dot"></span><?php endif; ?>
                </p>
                <p class="messages-list-item-preview"><?= h($conversation['last_body']) ?></p>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="messages-thread">
        <?php if (!$activeCounterpartId || !$activeCounterpart): ?>
            <div class="messages-thread-empty">Select a conversation to start messaging.</div>
        <?php else: ?>
            <div class="messages-thread-header"><?= h($activeCounterpart['full_name']) ?></div>
            <div class="messages-thread-body">
                <?php foreach ($thread as $message): ?>
                    <div class="message-bubble<?= (int) $message['sender_id'] === $userId ? ' is-mine' : '' ?>">
                        <?= nl2br(h($message['body'])) ?>
                        <span class="message-bubble-time"><?= h(date('M j, g:i A', strtotime($message['created_at']))) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="post" action="<?= h(base_url('messages/send.php')) ?>" class="messages-thread-form">
                <?= Csrf::field() ?>
                <input type="hidden" name="recipient_id" value="<?= $activeCounterpartId ?>">
                <input type="text" name="body" class="form-control" placeholder="Write a message…" required autocomplete="off">
                <button type="submit" class="btn btn-primary">Send</button>
            </form>
        <?php endif; ?>
    </div>
</div>

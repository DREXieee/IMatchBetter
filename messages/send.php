<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Message;
use IMatchBetter\Models\Notification;

Guard::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/' . Auth::role() . '/messages.php');
}

Csrf::verifyRequestOrFail();

$userId = (int) Auth::id();
$recipientId = (int) ($_POST['recipient_id'] ?? 0);
$body = trim($_POST['body'] ?? '');

if ($recipientId > 0 && $body !== '' && Message::canMessage($userId, $recipientId)) {
    Message::send($userId, $recipientId, $body);
    Notification::create(
        $recipientId,
        'new_message',
        Auth::fullName() . ' sent you a message.',
        $userId,
        'message'
    );
} else {
    flash('error', 'That message could not be sent.');
}

redirect('/' . Auth::role() . '/messages.php?with=' . $recipientId);

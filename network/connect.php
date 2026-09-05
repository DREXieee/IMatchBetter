<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Connection;
use IMatchBetter\Models\Notification;
use IMatchBetter\Models\User;

Guard::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/' . Auth::role() . '/network.php');
}

Csrf::verifyRequestOrFail();

$userId = (int) Auth::id();
$recipientId = (int) ($_POST['recipient_id'] ?? 0);
$recipient = $recipientId > 0 ? User::findById($recipientId) : null;

if ($recipient) {
    $connectionId = Connection::sendRequest($userId, $recipientId);

    if ($connectionId !== null) {
        Notification::create(
            $recipientId,
            'connection_request',
            Auth::fullName() . ' wants to connect with you.',
            $userId,
            'connection'
        );
        flash('success', 'Connection request sent.');
    }
}

redirect('/' . Auth::role() . '/network.php');

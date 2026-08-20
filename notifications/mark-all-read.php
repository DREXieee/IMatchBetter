<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Notification;

Guard::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/' . Auth::role() . '/notifications.php');
}

Csrf::verifyRequestOrFail();

Notification::markAllRead((int) Auth::id());

flash('success', 'All notifications marked as read.');
redirect('/' . Auth::role() . '/notifications.php');

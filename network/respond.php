<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Connection;
use IMatchBetter\Models\Notification;

Guard::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/' . Auth::role() . '/network.php');
}

Csrf::verifyRequestOrFail();

$userId = (int) Auth::id();
$connectionId = (int) ($_POST['connection_id'] ?? 0);
$accept = !empty($_POST['accept']);

$connection = null;
if ($connectionId > 0) {
    $accepted = Connection::respond($connectionId, $userId, $accept);

    if ($accepted && $accept) {
        // Find the requester to notify them back — respond() already verified this
        // connection belongs to $userId, so a direct lookup here is safe.
        $stmt = \IMatchBetter\Config\Database::connection()->prepare('SELECT requester_id FROM connections WHERE id = ?');
        $stmt->execute([$connectionId]);
        $requesterId = (int) $stmt->fetchColumn();

        if ($requesterId > 0) {
            Notification::create(
                $requesterId,
                'connection_accepted',
                Auth::fullName() . ' accepted your connection request.',
                $userId,
                'connection'
            );
        }
    }

    flash('success', $accept ? 'Connection accepted.' : 'Request declined.');
}

redirect('/' . Auth::role() . '/network.php');

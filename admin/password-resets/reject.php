<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\PasswordResetRequest;
use IMatchBetter\Services\AuditLogger;
use IMatchBetter\Services\Mailer;

Guard::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/password-resets/pending.php');
}

Csrf::verifyRequestOrFail();

$id = (int) ($_POST['id'] ?? 0);
$request = PasswordResetRequest::find($id);

if (!$request || $request['status'] !== 'pending') {
    flash('error', 'Request not found or already handled.');
    redirect('/admin/password-resets/pending.php');
}

PasswordResetRequest::reject($id, (int) Auth::id());
AuditLogger::log((int) Auth::id(), 'password_reset_rejected', 'password_reset_request', $id, $request['email']);

// Emailed, not an in-app notification — the requester can't log in to see one, and the
// wording stays generic (no specifics on why) since this is a security gate, not support.
Mailer::send(
    $request['email'],
    $request['full_name'],
    'Update on your password reset request',
    'password-reset-rejected'
);

flash('info', 'Password reset request rejected.');
redirect('/admin/password-resets/pending.php');

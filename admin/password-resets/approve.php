<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Notification;
use IMatchBetter\Models\PasswordReset;
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

PasswordResetRequest::approve($id, (int) Auth::id());
AuditLogger::log((int) Auth::id(), 'password_reset_approved', 'password_reset_request', $id, $request['email']);

$token = PasswordReset::create((int) $request['user_id']);
$resetUrl = base_url('reset-password.php?token=' . $token);
$emailSent = Mailer::send($request['email'], $request['full_name'], 'Reset your IMatchBetter password', 'password-reset', ['resetUrl' => $resetUrl]);

$notificationId = Notification::create((int) $request['user_id'], 'password_reset_approved', 'Your password reset request was approved. Check your email for the reset link.', $id, 'password_reset_request');
if ($emailSent) {
    Notification::markEmailSent($notificationId);
}

flash('success', 'Reset link sent to ' . $request['email'] . '.');
redirect('/admin/password-resets/pending.php');

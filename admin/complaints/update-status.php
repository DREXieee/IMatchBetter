<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Complaint;
use IMatchBetter\Models\Notification;
use IMatchBetter\Services\AuditLogger;

Guard::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/complaints/index.php');
}

Csrf::verifyRequestOrFail();

$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$resolutionNotes = trim($_POST['resolution_notes'] ?? '');
$validStatuses = ['investigating', 'resolved', 'dismissed'];

$complaint = Complaint::find($id);

if (!$complaint || !in_array($status, $validStatuses, true)) {
    flash('error', 'Complaint not found.');
    redirect('/admin/complaints/index.php');
}

Complaint::updateStatus($id, $status, $resolutionNotes ?: null, (int) Auth::id());
AuditLogger::log((int) Auth::id(), 'complaint_' . $status, 'complaint', $id, $resolutionNotes ?: null);

Notification::create(
    (int) $complaint['complainant_id'],
    'complaint_status_changed',
    'Your complaint is now: ' . $status . '.',
    $id,
    'complaint'
);

flash('success', 'Complaint updated.');
redirect('/admin/complaints/index.php');

<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Application;
use IMatchBetter\Models\Interview;

Guard::requireRole('employer');

$id = (int) ($_GET['id'] ?? $_GET['application_id'] ?? 0);
$application = Application::findWithContext($id);

if (!$application || !Application::isForEmployer($id, (int) Auth::id())) {
    http_response_code(404);
    exit('Application not found.');
}

$statuses = ['submitted' => 'Submitted', 'reviewed' => 'Reviewed', 'interview' => 'Interview', 'rejected' => 'Rejected', 'hired' => 'Hired'];
$interview = Interview::findByApplication($id);

$role = 'employer';
$pageTitle = 'Applicant — ' . h($application['applicant_name']) . ' — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1><?= h($application['applicant_name']) ?></h1>
        <p>Applied for <strong><?= h($application['job_title']) ?></strong></p>

        <div class="card" style="max-width:640px;">
            <p><strong>Email:</strong> <?= h($application['applicant_email']) ?></p>
            <p><strong>Applied:</strong> <?= h(date('M j, Y g:i A', strtotime($application['applied_at']))) ?></p>
            <p><strong>Status:</strong> <?php $status = $application['status']; require __DIR__ . '/../../includes/partials/status-badge.php'; ?></p>

            <?php if (!empty($application['cover_letter'])): ?>
                <h3>Cover letter</h3>
                <p style="white-space:pre-line;"><?= h($application['cover_letter']) ?></p>
            <?php endif; ?>

            <p>
                <a href="<?= h(base_url('download.php?resume_id=' . $application['resume_id'])) ?>" class="btn btn-outline">Download Resume</a>
                <a href="<?= h(base_url('employer/messages.php?with=' . (int) $application['applicant_id'])) ?>" class="btn btn-outline">Message Applicant</a>
            </p>

            <p><a href="<?= h(base_url('complaints/create.php?against_type=application&against_id=' . $application['id'])) ?>" class="form-hint">Report this applicant</a></p>

            <form method="post" action="<?= h(base_url('employer/applicants/update-status.php')) ?>" style="margin-top:1.5rem;">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int) $application['id'] ?>">
                <div class="form-group">
                    <label class="form-label" for="status">Update status</label>
                    <select class="form-control" id="status" name="status">
                        <?php foreach ($statuses as $value => $label): ?>
                            <option value="<?= h($value) ?>" <?= $application['status'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </form>
        </div>

        <?php if ($application['status'] === 'interview'): ?>
            <div class="card" style="max-width:640px; margin-top:1.5rem;">
                <h3><?= $interview ? 'Reschedule Interview' : 'Schedule Interview' ?></h3>
                <?php if ($interview): ?>
                    <p>Currently: <strong><?= h(date('M j, Y g:i A', strtotime($interview['scheduled_at']))) ?></strong> &middot; <?= h(ucfirst($interview['mode'])) ?>
                        &middot; <?php $status = $interview['status']; require __DIR__ . '/../../includes/partials/status-badge.php'; ?></p>
                <?php endif; ?>
                <form method="post" action="<?= h(base_url('employer/interviews/schedule.php')) ?>">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="application_id" value="<?= (int) $application['id'] ?>">
                    <div class="form-group">
                        <label class="form-label" for="scheduled_at">Date &amp; time</label>
                        <input class="form-control" type="datetime-local" id="scheduled_at" name="scheduled_at" value="<?= $interview ? h(date('Y-m-d\TH:i', strtotime($interview['scheduled_at']))) : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="mode">Mode</label>
                        <select class="form-control" id="mode" name="mode">
                            <option value="video" <?= ($interview['mode'] ?? '') === 'video' ? 'selected' : '' ?>>Video call</option>
                            <option value="onsite" <?= ($interview['mode'] ?? '') === 'onsite' ? 'selected' : '' ?>>Onsite</option>
                            <option value="phone" <?= ($interview['mode'] ?? '') === 'phone' ? 'selected' : '' ?>>Phone</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="location_or_link">Location or link</label>
                        <input class="form-control" type="text" id="location_or_link" name="location_or_link" value="<?= h($interview['location_or_link'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="notes">Notes (optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"><?= h($interview['notes'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><?= $interview ? 'Update Interview' : 'Schedule Interview' ?></button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (in_array($application['status'], ['hired', 'rejected'], true)): ?>
            <div class="card" style="max-width:640px; margin-top:1.5rem;">
                <h3>Rate this applicant</h3>
                <p>Leave feedback about your experience with this candidate.</p>
                <a href="<?= h(base_url('employer/reviews/create.php?application_id=' . $application['id'])) ?>" class="btn btn-outline">Rate Applicant</a>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

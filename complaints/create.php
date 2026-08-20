<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Application;
use IMatchBetter\Models\Complaint;
use IMatchBetter\Models\Notification;

Guard::requireVerified();

$role = Auth::role();
$userId = (int) Auth::id();
$errors = [];

$categories = [
    'fake_job' => 'Fake or misleading job posting',
    'unresponsive_employer' => 'Unresponsive employer',
    'unresponsive_applicant' => 'Unresponsive applicant',
    'inappropriate_conduct' => 'Inappropriate conduct',
    'other' => 'Other',
];

/**
 * A job can be reported by anyone; an application can only be reported by one of its two
 * parties (the applicant, or the employer who owns the job) — never an arbitrary user.
 */
function complainantMayReport(string $type, int $id, int $reporterId): bool
{
    if ($type === 'job') {
        return true;
    }

    if ($type === 'application') {
        $application = Application::find($id);

        return $application !== null
            && ((int) $application['applicant_id'] === $reporterId || Application::isForEmployer($id, $reporterId));
    }

    return false;
}

$againstType = $_GET['against_type'] ?? $_POST['against_type'] ?? '';
$againstId = (int) ($_GET['against_id'] ?? $_POST['against_id'] ?? 0);
$targetLabel = in_array($againstType, ['job', 'application'], true) && $againstId > 0 && complainantMayReport($againstType, $againstId, $userId)
    ? Complaint::describeTarget($againstType, $againstId)
    : null;

const COMPLAINTS_PER_DAY = 5;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    if (Complaint::countRecentByUser($userId) >= COMPLAINTS_PER_DAY) {
        flash('error', "You've reached the daily limit for filing complaints. Please try again tomorrow.");
        redirect('/' . $role . '/complaints/index.php');
    }

    $category = $_POST['category'] ?? '';
    $message = trim($_POST['message'] ?? '');

    if ($targetLabel === null) {
        $errors['target'] = 'This complaint target is no longer valid. Please use the "Report" link on the relevant page.';
    }
    if (!isset($categories[$category])) {
        $errors['category'] = 'Please choose a category.';
    }
    if ($message === '') {
        $errors['message'] = 'Please describe the issue.';
    }

    if (empty($errors)) {
        Complaint::create($userId, $againstType, $againstId, $category, $message);

        foreach (Notification::adminUserIds() as $adminId) {
            Notification::create(
                (int) $adminId,
                'complaint_filed',
                'A new complaint was filed (' . $categories[$category] . ').',
                null,
                'complaint'
            );
        }

        flash('success', 'Your complaint was submitted. An admin will review it.');
        redirect('/' . $role . '/complaints/index.php');
    }
}

$pageTitle = 'File a Complaint — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>File a Complaint</h1>

        <?php if ($targetLabel === null): ?>
            <div class="card empty-state">
                Complaints must be filed from the job, application, or profile you're reporting.
                Look for a "Report" link there, or start from
                <a href="<?= h(base_url('jobs.php')) ?>">Find Jobs</a>
                or <a href="<?= h(base_url($role . '/complaints/index.php')) ?>">your complaints list</a>.
            </div>
        <?php else: ?>
            <form method="post" action="<?= h(base_url('complaints/create.php')) ?>" class="card" style="max-width:640px;">
                <?= Csrf::field() ?>
                <input type="hidden" name="against_type" value="<?= h($againstType) ?>">
                <input type="hidden" name="against_id" value="<?= (int) $againstId ?>">

                <div class="form-group">
                    <label class="form-label">Reporting</label>
                    <p><?= h($targetLabel) ?></p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="category">Category</label>
                    <select class="form-control" id="category" name="category">
                        <?php foreach ($categories as $value => $label): ?>
                            <option value="<?= h($value) ?>"><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['category'])): ?><div class="form-error"><?= h($errors['category']) ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="message">What happened?</label>
                    <textarea class="form-control" id="message" name="message" rows="5"></textarea>
                    <?php if (!empty($errors['message'])): ?><div class="form-error"><?= h($errors['message']) ?></div><?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Submit Complaint</button>
            </form>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

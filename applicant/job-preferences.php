<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\JobPreference;

Guard::requireRole('applicant');

$userId = (int) Auth::id();
$preference = JobPreference::findByApplicantId($userId) ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    $employmentType = $_POST['preferred_employment_type'] ?? 'any';
    $validTypes = ['full_time', 'part_time', 'contract', 'internship', 'remote', 'any'];
    if (!in_array($employmentType, $validTypes, true)) {
        $employmentType = 'any';
    }

    $location = trim($_POST['preferred_location'] ?? '');
    $salaryMin = trim($_POST['salary_min'] ?? '');
    $salaryMax = trim($_POST['salary_max'] ?? '');

    JobPreference::upsert(
        $userId,
        $employmentType,
        $location ?: null,
        $salaryMin !== '' ? (int) $salaryMin : null,
        $salaryMax !== '' ? (int) $salaryMax : null
    );

    flash('success', 'Job preferences saved.');
    redirect('/applicant/job-preferences.php');
}

$employmentTypes = ['any' => 'Any', 'full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'internship' => 'Internship', 'remote' => 'Remote'];

$role = 'applicant';
$pageTitle = 'Job Preferences — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>Job Preferences</h1>
        <p>Tell us what you're looking for so we can recommend better matches.</p>

        <form method="post" action="<?= h(base_url('applicant/job-preferences.php')) ?>" class="card" style="max-width:640px;">
            <?= Csrf::field() ?>

            <div class="form-group">
                <label class="form-label" for="preferred_employment_type">Preferred employment type</label>
                <select class="form-control" id="preferred_employment_type" name="preferred_employment_type">
                    <?php foreach ($employmentTypes as $value => $label): ?>
                        <option value="<?= h($value) ?>" <?= ($preference['preferred_employment_type'] ?? 'any') === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="preferred_location">Preferred location</label>
                <input class="form-control" type="text" id="preferred_location" name="preferred_location" value="<?= h($preference['preferred_location'] ?? '') ?>" placeholder="e.g. Manila, Philippines or Remote">
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="salary_min">Minimum salary (optional)</label>
                    <input class="form-control" type="number" id="salary_min" name="salary_min" min="0" value="<?= h((string) ($preference['salary_min'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="salary_max">Maximum salary (optional)</label>
                    <input class="form-control" type="number" id="salary_max" name="salary_max" min="0" value="<?= h((string) ($preference['salary_max'] ?? '')) ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Preferences</button>
        </form>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

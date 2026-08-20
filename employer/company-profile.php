<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\EmployerProfile;
use IMatchBetter\Models\EmployerReview;
use IMatchBetter\Services\FileUploadService;

Guard::requireRole('employer');

$userId = (int) Auth::id();
$profile = EmployerProfile::findByUserId($userId);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    $companyName = trim($_POST['company_name'] ?? '');
    $companyWebsite = trim($_POST['company_website'] ?? '');
    $companyDescription = trim($_POST['company_description'] ?? '');
    $logoPath = null;

    if ($companyName === '') {
        $errors['company_name'] = 'Company name is required.';
    }

    if (!empty($_FILES['logo']) && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $stored = FileUploadService::storeLogo($_FILES['logo']);
            $logoPath = $stored['file_path'];
        } catch (\RuntimeException $e) {
            $errors['logo'] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        EmployerProfile::updateProfile($userId, $companyName, $companyWebsite ?: null, $companyDescription ?: null, $logoPath);
        flash('success', 'Company profile updated.');
        redirect('/employer/company-profile.php');
    }

    $profile = array_merge($profile ?? [], [
        'company_name' => $companyName,
        'company_website' => $companyWebsite,
        'company_description' => $companyDescription,
    ]);
}

$reviewStats = EmployerReview::statsForEmployer($userId);
$reviews = EmployerReview::forEmployer($userId);

$role = 'employer';
$pageTitle = 'Company Profile — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>Company Profile</h1>

        <form method="post" action="<?= h(base_url('employer/company-profile.php')) ?>" enctype="multipart/form-data" class="card" style="max-width:640px;">
            <?= Csrf::field() ?>

            <?php if (!empty($profile['logo_path'])): ?>
                <img src="<?= h(base_url('download.php?logo=' . basename($profile['logo_path']))) ?>" alt="Company logo" style="max-width:120px; margin-bottom:1rem; border-radius: var(--radius-sm);">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="company_name">Company name</label>
                <input class="form-control" type="text" id="company_name" name="company_name" value="<?= h($profile['company_name'] ?? '') ?>" required>
                <?php if (!empty($errors['company_name'])): ?><div class="form-error"><?= h($errors['company_name']) ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="company_website">Website</label>
                <input class="form-control" type="url" id="company_website" name="company_website" value="<?= h($profile['company_website'] ?? '') ?>" placeholder="https://">
            </div>

            <div class="form-group">
                <label class="form-label" for="company_description">Description</label>
                <textarea class="form-control" id="company_description" name="company_description" rows="4"><?= h($profile['company_description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="logo">Logo (PNG, JPEG, or WEBP — max 2MB)</label>
                <input class="form-control" type="file" id="logo" name="logo" accept=".png,.jpg,.jpeg,.webp">
                <?php if (!empty($errors['logo'])): ?><div class="form-error"><?= h($errors['logo']) ?></div><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
        </form>

        <div class="card" style="max-width:640px; margin-top:1.5rem;">
            <h3>Reviews from Applicants</h3>
            <p class="star-rating"><?= str_repeat('★', (int) round($reviewStats['avg_rating'])) . str_repeat('☆', 5 - (int) round($reviewStats['avg_rating'])) ?>
                <span style="color:var(--color-text-muted); font-size:0.85rem;">(<?= (int) $reviewStats['review_count'] ?> review<?= (int) $reviewStats['review_count'] === 1 ? '' : 's' ?>)</span></p>

            <?php if (empty($reviews)): ?>
                <p class="empty-state">No approved reviews yet.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div style="border-top:1px solid var(--color-border); padding-top:0.75rem; margin-top:0.75rem;">
                        <p class="star-rating" style="margin-bottom:0.25rem;"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?></p>
                        <?php if (!empty($review['title'])): ?><p style="font-weight:600; margin-bottom:0.25rem;"><?= h($review['title']) ?></p><?php endif; ?>
                        <p style="margin-bottom:0.25rem;"><?= h($review['body']) ?></p>
                        <p class="form-hint">— <?= h($review['applicant_name']) ?>, <?= h(date('M j, Y', strtotime($review['created_at']))) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

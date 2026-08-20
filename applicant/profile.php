<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\ApplicantProfile;
use IMatchBetter\Models\Certificate;
use IMatchBetter\Models\Resume;
use IMatchBetter\Models\Skill;
use IMatchBetter\Services\FileUploadService;

Guard::requireRole('applicant');

$userId = (int) Auth::id();
$profile = ApplicantProfile::findByUserId($userId) ?? [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    $headline = trim($_POST['headline'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $school = trim($_POST['school'] ?? '');
    $degree = trim($_POST['degree'] ?? '');
    $fieldOfStudy = trim($_POST['field_of_study'] ?? '');
    $graduationYear = trim($_POST['graduation_year'] ?? '');
    $profileVisibility = isset($_POST['profile_visibility']);

    ApplicantProfile::update(
        $userId, $headline, $bio, $location, $skills,
        $school ?: null, $degree ?: null, $fieldOfStudy ?: null,
        $graduationYear !== '' ? (int) $graduationYear : null,
        $profileVisibility
    );
    Skill::syncApplicantSkills($userId, Skill::parseList($skills));

    $uploadedSomething = false;

    if (!empty($_FILES['resume']) && ($_FILES['resume']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $stored = FileUploadService::storeResume($_FILES['resume']);
            $resumeId = Resume::create(
                $userId,
                $stored['original_filename'],
                $stored['stored_filename'],
                $stored['file_path'],
                $stored['mime_type'],
                $stored['file_size']
            );
            ApplicantProfile::setCurrentResume($userId, $resumeId);
            $uploadedSomething = true;
        } catch (\RuntimeException $e) {
            $errors['resume'] = $e->getMessage();
        }
    }

    if (!empty($_FILES['certificate']) && ($_FILES['certificate']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $stored = FileUploadService::storeCertificate($_FILES['certificate']);
            Certificate::create(
                $userId,
                $stored['original_filename'],
                $stored['stored_filename'],
                $stored['file_path'],
                $stored['mime_type'],
                $stored['file_size']
            );
            $uploadedSomething = true;
        } catch (\RuntimeException $e) {
            $errors['certificate'] = $e->getMessage();
        }
    }

    flash('success', $uploadedSomething ? 'Profile updated and file uploaded.' : 'Profile updated.');

    if (empty($errors)) {
        redirect('/applicant/profile.php');
    }

    $profile = array_merge($profile, compact('headline', 'bio', 'location', 'skills', 'school', 'degree', 'fieldOfStudy', 'graduationYear'));
}

$resumes = Resume::forApplicant($userId);
$certificates = Certificate::forApplicant($userId);

$role = 'applicant';
$pageTitle = 'My Profile — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>My Profile</h1>

        <form method="post" action="<?= h(base_url('applicant/profile.php')) ?>" enctype="multipart/form-data" class="card" style="max-width:640px;">
            <?= Csrf::field() ?>

            <div class="form-group">
                <label class="form-label" for="headline">Headline</label>
                <input class="form-control" type="text" id="headline" name="headline" value="<?= h($profile['headline'] ?? '') ?>" placeholder="e.g. Junior Web Developer">
            </div>

            <div class="form-group">
                <label class="form-label" for="location">Location</label>
                <input class="form-control" type="text" id="location" name="location" value="<?= h($profile['location'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="skills">Skills (comma-separated)</label>
                <input class="form-control" type="text" id="skills" name="skills" value="<?= h($profile['skills'] ?? '') ?>" placeholder="HTML, CSS, JavaScript">
            </div>

            <div class="form-group">
                <label class="form-label" for="bio">About you</label>
                <textarea class="form-control" id="bio" name="bio" rows="4"><?= h($profile['bio'] ?? '') ?></textarea>
            </div>

            <h3>Education</h3>
            <p class="form-hint">Optional — used by employers searching the graduate talent database.</p>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="school">School</label>
                    <input class="form-control" type="text" id="school" name="school" value="<?= h($profile['school'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="degree">Degree</label>
                    <input class="form-control" type="text" id="degree" name="degree" value="<?= h($profile['degree'] ?? '') ?>" placeholder="e.g. BS Computer Science">
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="field_of_study">Field of study</label>
                    <input class="form-control" type="text" id="field_of_study" name="field_of_study" value="<?= h($profile['field_of_study'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="graduation_year">Graduation year</label>
                    <input class="form-control" type="number" id="graduation_year" name="graduation_year" min="1950" max="2100" value="<?= h((string) ($profile['graduation_year'] ?? '')) ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="profile_visibility" value="1" <?= ($profile['profile_visibility'] ?? 1) ? 'checked' : '' ?>>
                    Make my profile visible to employers in the Graduate Talent Database
                </label>
            </div>

            <div class="form-group">
                <label class="form-label" for="resume">Upload resume (PDF, DOC, DOCX — max 5MB)</label>
                <input class="form-control" type="file" id="resume" name="resume" accept=".pdf,.doc,.docx">
                <?php if (!empty($errors['resume'])): ?><div class="form-error"><?= h($errors['resume']) ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="certificate">Upload certificate (PDF, DOC, DOCX, JPG, PNG — max 5MB)</label>
                <input class="form-control" type="file" id="certificate" name="certificate" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <?php if (!empty($errors['certificate'])): ?><div class="form-error"><?= h($errors['certificate']) ?></div><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Save Profile</button>
        </form>

        <div class="card" style="max-width:640px; margin-top:1.5rem;">
            <h3>My Resumes</h3>
            <?php if (empty($resumes)): ?>
                <p class="empty-state">No resume uploaded yet.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($resumes as $resume): ?>
                        <li>
                            <a href="<?= h(base_url('download.php?resume_id=' . $resume['id'])) ?>"><?= h($resume['original_filename']) ?></a>
                            — <?= h(date('M j, Y', strtotime($resume['uploaded_at']))) ?>
                            <?php if (($profile['current_resume_id'] ?? null) == $resume['id']): ?>
                                <span class="badge badge-approved">active</span>
                            <?php endif; ?>
                            <form method="post" action="<?= h(base_url('applicant/resumes/delete.php')) ?>" style="display:inline;" onsubmit="return confirm('Delete this resume?');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $resume['id'] ?>">
                                <button type="submit" class="btn btn-outline" style="padding:0.1rem 0.5rem; min-height:auto; font-size:0.8rem;">Delete</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card" style="max-width:640px; margin-top:1.5rem;">
            <h3>My Certificates</h3>
            <?php if (empty($certificates)): ?>
                <p class="empty-state">No certificates uploaded yet.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($certificates as $certificate): ?>
                        <li>
                            <a href="<?= h(base_url('download.php?certificate_id=' . $certificate['id'])) ?>"><?= h($certificate['original_filename']) ?></a>
                            — <?= h(date('M j, Y', strtotime($certificate['uploaded_at']))) ?>
                            <form method="post" action="<?= h(base_url('applicant/certificates/delete.php')) ?>" style="display:inline;" onsubmit="return confirm('Delete this certificate?');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $certificate['id'] ?>">
                                <button type="submit" class="btn btn-outline" style="padding:0.1rem 0.5rem; min-height:auto; font-size:0.8rem;">Delete</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

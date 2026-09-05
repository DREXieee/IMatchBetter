<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\ApplicantProfile;
use IMatchBetter\Models\Certificate;
use IMatchBetter\Models\JobPreference;
use IMatchBetter\Models\Resume;
use IMatchBetter\Models\Skill;
use IMatchBetter\Models\User;
use IMatchBetter\Services\FileUploadService;

Guard::requireRole('applicant');

$userId = (int) Auth::id();
$errors = [];
$isNew = !empty($_GET['new']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    $isNew = !empty($_POST['new']);

    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $streetAddress = trim($_POST['street_address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $zipCode = trim($_POST['zip_code'] ?? '');

    $headline = trim($_POST['headline'] ?? '');
    $school = trim($_POST['school'] ?? '');
    $degree = trim($_POST['degree'] ?? '');
    $fieldOfStudy = trim($_POST['field_of_study'] ?? '');
    $graduationYear = trim($_POST['graduation_year'] ?? '');
    $experienceLevel = trim($_POST['experience_level'] ?? '');
    $preferredLocation = trim($_POST['preferred_location'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $expectedSalaryMin = trim($_POST['expected_salary_min'] ?? '');
    $expectedSalaryMax = trim($_POST['expected_salary_max'] ?? '');

    $skills = trim($_POST['skills'] ?? '');
    $profileVisibility = isset($_POST['profile_visibility']);

    if ($fullName === '') {
        $errors['full_name'] = 'Full name is required.';
    }

    if (empty($errors)) {
        User::updateContactInfo($userId, $fullName, $phone ?: null);

        ApplicantProfile::update($userId, [
            'headline' => $headline,
            'bio' => $bio,
            'skills' => $skills,
            'school' => $school ?: null,
            'degree' => $degree ?: null,
            'field_of_study' => $fieldOfStudy ?: null,
            'graduation_year' => $graduationYear !== '' ? (int) $graduationYear : null,
            'profile_visibility' => $profileVisibility ? 1 : 0,
            'date_of_birth' => $dateOfBirth ?: null,
            'gender' => $gender ?: null,
            'street_address' => $streetAddress ?: null,
            'city' => $city ?: null,
            'province' => $province ?: null,
            'zip_code' => $zipCode ?: null,
            'experience_level' => $experienceLevel ?: null,
            'expected_salary_min' => $expectedSalaryMin !== '' ? (int) $expectedSalaryMin : null,
            'expected_salary_max' => $expectedSalaryMax !== '' ? (int) $expectedSalaryMax : null,
            // "location" already means the applicant's home city elsewhere in the app
            // (profile view, talent search) — keep it in sync with the address city here.
            'location' => $city,
        ]);

        Skill::syncApplicantSkills($userId, Skill::parseList($skills));

        if ($preferredLocation !== '') {
            $existingPref = JobPreference::findByApplicantId($userId);
            JobPreference::upsert(
                $userId,
                $existingPref['preferred_employment_type'] ?? 'any',
                $preferredLocation,
                $existingPref['salary_min'] ?? null,
                $existingPref['salary_max'] ?? null
            );
        }

        // Validate every uploaded file up front, before persisting any of them — otherwise
        // a rejected photo could still leave a freshly-saved resume/certificate behind, and
        // resubmitting after fixing the photo would duplicate those rows.
        $hasPhoto = !empty($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $hasResume = !empty($_FILES['resume']) && ($_FILES['resume']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $certificateFiles = [];
        if (!empty($_FILES['certificates'])) {
            foreach (array_keys($_FILES['certificates']['name']) as $i) {
                if (($_FILES['certificates']['name'][$i] ?? '') === '') {
                    continue;
                }
                $certificateFiles[] = [
                    'name' => $_FILES['certificates']['name'][$i],
                    'type' => $_FILES['certificates']['type'][$i],
                    'tmp_name' => $_FILES['certificates']['tmp_name'][$i],
                    'error' => $_FILES['certificates']['error'][$i],
                    'size' => $_FILES['certificates']['size'][$i],
                ];
            }
        }

        if ($hasPhoto) {
            try {
                FileUploadService::validatePhoto($_FILES['photo']);
            } catch (\RuntimeException $e) {
                $errors['photo'] = $e->getMessage();
            }
        }
        if ($hasResume) {
            try {
                FileUploadService::validateResume($_FILES['resume']);
            } catch (\RuntimeException $e) {
                $errors['resume'] = $e->getMessage();
            }
        }
        foreach ($certificateFiles as $file) {
            try {
                FileUploadService::validateCertificate($file);
            } catch (\RuntimeException $e) {
                $errors['certificates'] = $e->getMessage();
                break;
            }
        }

        if (empty($errors)) {
            if ($hasPhoto) {
                $stored = FileUploadService::storePhoto($_FILES['photo']);
                ApplicantProfile::updatePhoto($userId, $stored['file_path']);
            }

            if ($hasResume) {
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
            }

            foreach ($certificateFiles as $file) {
                $stored = FileUploadService::storeCertificate($file);
                Certificate::create(
                    $userId,
                    $stored['original_filename'],
                    $stored['stored_filename'],
                    $stored['file_path'],
                    $stored['mime_type'],
                    $stored['file_size']
                );
            }

            flash('success', $isNew ? 'Your profile is ready. Welcome to IMatchBetter!' : 'Profile updated.');
            redirect($isNew ? '/applicant/dashboard.php' : '/applicant/profile.php');
        }
    }
}

$user = User::findById($userId);
$profile = ApplicantProfile::findByUserId($userId) ?? [];
$jobPreference = JobPreference::findByApplicantId($userId);
$existingSkills = $profile['skills'] ?? '';

$wizardStartStep = 1;
if (!empty($errors)) {
    $wizardStartStep = array_intersect(['photo', 'resume', 'certificates'], array_keys($errors)) ? 4 : 1;
}

$role = 'applicant';
$pageTitle = ($isNew ? 'Complete your profile' : 'Update your profile') . ' — IMatchBetter';
$extraStylesheets = ['css/dashboard.css', 'css/wizard-auth.css'];
$extraScripts = ['js/step-wizard.js', 'js/skill-chips.js', 'js/avatar-preview.js'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <div class="card" style="max-width:720px; margin:0 auto;">
            <p class="form-hint" style="margin-bottom:0;">Welcome to IMatchBetter</p>
            <h1><?= $isNew ? 'Complete your profile' : 'Update your information' ?></h1>
            <p class="form-hint">Help employers know you and improve your job matches</p>

            <form method="post" action="<?= h(base_url('applicant/profile-wizard.php')) ?>" enctype="multipart/form-data" novalidate data-step-wizard data-start-step="<?= (int) $wizardStartStep ?>">
                <?= Csrf::field() ?>
                <input type="hidden" name="new" value="<?= $isNew ? 1 : 0 ?>">

                <div class="step-progress" data-step-progress>
                    <div class="step-progress-item" data-step-item="1"><span class="step-progress-circle">1</span></div>
                    <div class="step-progress-line"></div>
                    <div class="step-progress-item" data-step-item="2"><span class="step-progress-circle">2</span></div>
                    <div class="step-progress-line"></div>
                    <div class="step-progress-item" data-step-item="3"><span class="step-progress-circle">3</span></div>
                    <div class="step-progress-line"></div>
                    <div class="step-progress-item" data-step-item="4"><span class="step-progress-circle">4</span></div>
                </div>

                <!-- Step 1: identity, contact, address -->
                <fieldset data-step-panel data-step="1">
                    <div class="avatar-upload">
                        <div class="avatar-upload-preview<?= !empty($profile['photo_path']) ? ' has-image' : '' ?>" data-avatar-preview>
                            <img data-avatar-preview-img alt="" <?= !empty($profile['photo_path']) ? 'src="' . h(base_url('download.php?photo=' . basename($profile['photo_path']))) . '"' : '' ?>>
                            <span class="avatar-upload-preview-icon">
                                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2Z"/><circle cx="12" cy="13" r="4"/></svg>
                            </span>
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline" data-avatar-trigger>Upload Photo</button>
                            <input type="file" name="photo" accept="image/png,image/jpeg,image/webp" class="avatar-upload-input" data-avatar-input>
                            <div class="form-hint">JPG or PNG, max 2mb</div>
                            <?php if (!empty($errors['photo'])): ?><div class="form-error"><?= h($errors['photo']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label" for="full_name">First name</label>
                            <input class="form-control" type="text" id="full_name" name="full_name" value="<?= h($fullName ?? $user['full_name'] ?? '') ?>" required>
                            <?php if (!empty($errors['full_name'])): ?><div class="form-error"><?= h($errors['full_name']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email_display">Email</label>
                            <input class="form-control" type="email" id="email_display" value="<?= h($user['email'] ?? '') ?>" disabled>
                        </div>
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input class="form-control" type="tel" id="phone" name="phone" value="<?= h($phone ?? $user['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="date_of_birth">Date of Birth</label>
                            <input class="form-control" type="date" id="date_of_birth" name="date_of_birth" value="<?= h($dateOfBirth ?? $profile['date_of_birth'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="gender">Gender (optional)</label>
                        <?php $genderValue = $gender ?? $profile['gender'] ?? ''; ?>
                        <select class="form-control" id="gender" name="gender">
                            <option value="Prefer not to say" <?= $genderValue === '' || $genderValue === 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
                            <option value="Male" <?= $genderValue === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= $genderValue === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Non-binary" <?= $genderValue === 'Non-binary' ? 'selected' : '' ?>>Non-binary</option>
                        </select>
                    </div>

                    <h3>Complete Address</h3>
                    <div class="form-group">
                        <label class="form-label" for="street_address">Street / Barangay</label>
                        <input class="form-control" type="text" id="street_address" name="street_address" value="<?= h($streetAddress ?? $profile['street_address'] ?? '') ?>">
                    </div>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label" for="city">City / Municipality</label>
                            <input class="form-control" type="text" id="city" name="city" value="<?= h($city ?? $profile['city'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="province">Province</label>
                            <input class="form-control" type="text" id="province" name="province" value="<?= h($province ?? $profile['province'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="zip_code">ZIP / Postal code</label>
                        <input class="form-control" type="text" id="zip_code" name="zip_code" value="<?= h($zipCode ?? $profile['zip_code'] ?? '') ?>">
                    </div>

                    <div class="step-actions">
                        <span></span>
                        <button type="button" class="btn btn-primary" data-step-next>Continue</button>
                    </div>
                </fieldset>

                <!-- Step 2: job info -->
                <fieldset data-step-panel data-step="2" hidden>
                    <div class="form-group">
                        <label class="form-label" for="headline">Current job title / status</label>
                        <input class="form-control" type="text" id="headline" name="headline" value="<?= h($headline ?? $profile['headline'] ?? '') ?>" placeholder="e.g. IT Professional">
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label" for="school">Education / School</label>
                            <input class="form-control" type="text" id="school" name="school" value="<?= h($school ?? $profile['school'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="graduation_year">Graduation Year</label>
                            <input class="form-control" type="number" id="graduation_year" name="graduation_year" min="1950" max="2100" value="<?= h((string) ($graduationYear ?? $profile['graduation_year'] ?? '')) ?>">
                        </div>
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label" for="degree">Degree (optional)</label>
                            <input class="form-control" type="text" id="degree" name="degree" value="<?= h($degree ?? $profile['degree'] ?? '') ?>" placeholder="e.g. BS Computer Science">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="field_of_study">Field of study (optional)</label>
                            <input class="form-control" type="text" id="field_of_study" name="field_of_study" value="<?= h($fieldOfStudy ?? $profile['field_of_study'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="experience_level">Experience Level</label>
                        <?php $experienceLevelValue = $experienceLevel ?? $profile['experience_level'] ?? ''; ?>
                        <select class="form-control" id="experience_level" name="experience_level">
                            <option value="" <?= $experienceLevelValue === '' ? 'selected' : '' ?> disabled>Select</option>
                            <option value="Student / Fresh graduate" <?= $experienceLevelValue === 'Student / Fresh graduate' ? 'selected' : '' ?>>Student / Fresh graduate</option>
                            <option value="Entry Level" <?= $experienceLevelValue === 'Entry Level' ? 'selected' : '' ?>>Entry Level</option>
                            <option value="Mid Level" <?= $experienceLevelValue === 'Mid Level' ? 'selected' : '' ?>>Mid Level</option>
                            <option value="Experienced Professional" <?= $experienceLevelValue === 'Experienced Professional' ? 'selected' : '' ?>>Experienced Professional</option>
                            <option value="Senior / Managerial" <?= $experienceLevelValue === 'Senior / Managerial' ? 'selected' : '' ?>>Senior / Managerial</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="preferred_location">Preferred job location</label>
                        <input class="form-control" type="text" id="preferred_location" name="preferred_location" value="<?= h($preferredLocation ?? $jobPreference['preferred_location'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="bio">Short bio / About me</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3"><?= h($bio ?? $profile['bio'] ?? '') ?></textarea>
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label" for="expected_salary_min">Expected salary range (min)</label>
                            <input class="form-control" type="number" id="expected_salary_min" name="expected_salary_min" min="0" value="<?= h((string) ($expectedSalaryMin ?? $profile['expected_salary_min'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="expected_salary_max">Expected salary range (max)</label>
                            <input class="form-control" type="number" id="expected_salary_max" name="expected_salary_max" min="0" value="<?= h((string) ($expectedSalaryMax ?? $profile['expected_salary_max'] ?? '')) ?>">
                        </div>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="btn btn-outline" data-step-prev>Back</button>
                        <button type="button" class="btn btn-primary" data-step-next>Continue</button>
                    </div>
                </fieldset>

                <!-- Step 3: skills -->
                <fieldset data-step-panel data-step="3" hidden>
                    <label class="form-label">What are your strongest skills?</label>
                    <p class="form-hint">Choose suggestions or add your own. Add at least one.</p>

                    <div data-skill-chips>
                        <input type="hidden" name="skills" data-chip-value value="<?= h($skills ?? $existingSkills) ?>">
                        <div class="chip-grid" data-chip-suggestions></div>
                        <div class="chip-input-row">
                            <input type="text" class="form-control" data-chip-input placeholder="Type a skill, e.g Data Analysis">
                            <button type="button" class="btn btn-outline" data-chip-add>Add skill</button>
                        </div>
                        <label class="form-label">Your Skills</label>
                        <div class="chip-selected-list" data-chip-selected></div>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="btn btn-outline" data-step-prev>Back</button>
                        <button type="button" class="btn btn-primary" data-step-next>Continue</button>
                    </div>
                </fieldset>

                <!-- Step 4: documents -->
                <fieldset data-step-panel data-step="4" hidden>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label" for="resume">Upload resume</label>
                            <div class="form-hint">PDF, DOC, or DOCX — max 5mb</div>
                            <input class="form-control" type="file" id="resume" name="resume" accept=".pdf,.doc,.docx">
                            <?php if (!empty($errors['resume'])): ?><div class="form-error"><?= h($errors['resume']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="certificates">Add certificates</label>
                            <div class="form-hint">PDF, JPG, or PNG — up to 5mb each</div>
                            <input class="form-control" type="file" id="certificates" name="certificates[]" accept=".pdf,.jpg,.jpeg,.png" multiple>
                            <?php if (!empty($errors['certificates'])): ?><div class="form-error"><?= h($errors['certificates']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="profile_visibility" value="1" <?= ($profile['profile_visibility'] ?? 1) ? 'checked' : '' ?>>
                            Make my profile visible to employers in the Graduate Talent Database
                        </label>
                    </div>

                    <div class="flash flash-info">
                        <strong>You're almost ready</strong><br>
                        Uploaded document names are saved to your account and remain available from your profile.
                    </div>

                    <div class="step-actions">
                        <button type="button" class="btn btn-outline" data-step-prev>Back</button>
                        <button type="submit" class="btn btn-primary">Save profile</button>
                    </div>
                </fieldset>
            </form>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

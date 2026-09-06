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

Guard::requireRole('applicant');

$userId = (int) Auth::id();
$user = User::findById($userId);
$profile = ApplicantProfile::findByUserId($userId) ?? [];
$jobPreference = JobPreference::findByApplicantId($userId);
$resumes = Resume::forApplicant($userId);
$certificates = Certificate::forApplicant($userId);
$skillNames = Skill::parseList($profile['skills'] ?? '');

$addressParts = array_filter([
    $profile['street_address'] ?? null,
    $profile['city'] ?? null,
    $profile['province'] ?? null,
    $profile['zip_code'] ?? null,
]);

$role = 'applicant';
$pageTitle = 'My Profile — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <div class="card" style="padding:0; overflow:hidden; margin-bottom:1.5rem;">
            <div style="background:var(--color-primary); height:90px;"></div>
            <div style="padding: 0 var(--space-4) var(--space-4); position:relative;">
                <?php if (!empty($profile['photo_path'])): ?>
                    <img
                        class="avatar avatar-lg"
                        style="border:4px solid var(--color-bg); margin-top:-44px;"
                        alt=""
                        src="<?= h(base_url('download.php?photo=' . basename($profile['photo_path']))) ?>"
                    >
                <?php else: ?>
                    <div class="avatar avatar-lg avatar-fallback" style="border:4px solid var(--color-bg); margin-top:-44px;"><?= h(initials($user['full_name'] ?? '')) ?></div>
                <?php endif; ?>
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                    <div>
                        <h1 style="margin-bottom:0.1rem;"><?= h($user['full_name'] ?? '') ?></h1>
                        <?php if (!empty($profile['headline'])): ?><p style="margin:0; font-weight:600;"><?= h($profile['headline']) ?></p><?php endif; ?>
                        <p class="form-hint" style="margin:0.2rem 0 0;">
                            <?= h(implode(', ', $addressParts)) ?: h($profile['location'] ?? '') ?>
                        </p>
                    </div>
                    <a href="<?= h(base_url('applicant/profile-wizard.php')) ?>" class="btn btn-outline">Edit Profile</a>
                </div>
            </div>
        </div>

        <div class="grid grid-2">
            <div>
                <div class="card" style="margin-bottom:1.5rem;">
                    <h3>About</h3>
                    <p style="margin:0;"><?= nl2br(h($profile['bio'] ?? '')) ?: '<span class="empty-state">No bio yet.</span>' ?></p>
                </div>

                <div class="card" style="margin-bottom:1.5rem;">
                    <h3>Skills</h3>
                    <?php if (empty($skillNames)): ?>
                        <p class="empty-state">No skills added yet.</p>
                    <?php else: ?>
                        <div class="chip-static-list">
                            <?php foreach ($skillNames as $skillName): ?>
                                <span class="chip-static"><?= h($skillName) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h3>Education &amp; Experience</h3>
                    <?php if (!empty($profile['school'])): ?>
                        <p style="margin:0;"><strong><?= h($profile['school']) ?></strong></p>
                        <p class="form-hint" style="margin:0 0 0.75rem;">
                            <?= h(trim(($profile['degree'] ?? '') . (!empty($profile['degree']) && !empty($profile['field_of_study']) ? ' · ' : '') . ($profile['field_of_study'] ?? ''))) ?>
                            <?php if (!empty($profile['graduation_year'])): ?> · Graduated <?= h((string) $profile['graduation_year']) ?><?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <p style="margin:0;"><strong>Experience</strong></p>
                    <p class="form-hint" style="margin:0 0 0.75rem;"><?= h($profile['experience_level'] ?? 'Not specified') ?></p>
                    <?php if (!empty($jobPreference['preferred_location'])): ?>
                        <p style="margin:0;"><strong>Preferred location</strong></p>
                        <p class="form-hint" style="margin:0 0 0.75rem;"><?= h($jobPreference['preferred_location']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($profile['expected_salary_min']) || !empty($profile['expected_salary_max'])): ?>
                        <p style="margin:0;"><strong>Expected salary</strong></p>
                        <p class="form-hint" style="margin:0;">
                            <?= $profile['expected_salary_min'] ? number_format((float) $profile['expected_salary_min']) : '' ?><?= !empty($profile['expected_salary_min']) && !empty($profile['expected_salary_max']) ? ' – ' : '' ?><?= $profile['expected_salary_max'] ? number_format((float) $profile['expected_salary_max']) : '' ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="card" style="margin-bottom:1.5rem;">
                    <h3>Documents</h3>
                    <p class="form-label" style="margin-bottom:0.3rem;">Resumes</p>
                    <?php if (empty($resumes)): ?>
                        <p class="empty-state">No resume uploaded yet.</p>
                    <?php else: ?>
                        <ul style="margin-top:0;">
                            <?php foreach ($resumes as $resume): ?>
                                <li>
                                    <a href="<?= h(base_url('download.php?resume_id=' . $resume['id'])) ?>"><?= h($resume['original_filename']) ?></a>
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

                    <p class="form-label" style="margin-bottom:0.3rem;">Certificates</p>
                    <?php if (empty($certificates)): ?>
                        <p class="empty-state">No certificates uploaded yet.</p>
                    <?php else: ?>
                        <ul style="margin-top:0;">
                            <?php foreach ($certificates as $certificate): ?>
                                <li>
                                    <a href="<?= h(base_url('download.php?certificate_id=' . $certificate['id'])) ?>"><?= h($certificate['original_filename']) ?></a>
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

                <div class="card">
                    <h3>Contact</h3>
                    <p style="margin:0;"><strong>Email</strong></p>
                    <p class="form-hint" style="margin:0 0 0.75rem;"><a href="mailto:<?= h($user['email'] ?? '') ?>"><?= h($user['email'] ?? '') ?></a></p>
                    <?php if (!empty($user['phone'])): ?>
                        <p style="margin:0;"><strong>Phone</strong></p>
                        <p class="form-hint" style="margin:0 0 0.75rem;"><?= h($user['phone']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($addressParts)): ?>
                        <p style="margin:0;"><strong>Address</strong></p>
                        <p class="form-hint" style="margin:0;"><?= h(implode(', ', $addressParts)) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

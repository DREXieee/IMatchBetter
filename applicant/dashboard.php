<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\ApplicantProfile;
use IMatchBetter\Models\Connection;
use IMatchBetter\Models\Notification;
use IMatchBetter\Models\SavedJob;
use IMatchBetter\Services\SkillMatchService;

Guard::requireRole('applicant');

$userId = (int) Auth::id();
$profile = ApplicantProfile::findByUserId($userId) ?? [];
$unreadCount = Notification::unreadCount($userId);
$topMatches = SkillMatchService::recommendedJobsForApplicant($userId, 3);
$savedJobsCount = count(SavedJob::forApplicant($userId));
$suggestions = Connection::suggestionsForUser($userId, 3);

$role = 'applicant';
$pageTitle = 'My Dashboard — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Welcome back, <?= h(Auth::fullName()) ?></h1>
            <p>Here's a quick look at your job search.</p>
        </div>

        <div class="dashboard-columns-3">
                <div>
                    <div class="card" style="text-align:center; margin-bottom:1.5rem;">
                        <?php if (!empty($profile['photo_path'])): ?>
                            <img class="avatar avatar-lg" style="margin:0 auto 0.75rem;" alt="" src="<?= h(base_url('download.php?photo=' . basename($profile['photo_path']))) ?>">
                        <?php else: ?>
                            <div class="avatar avatar-lg avatar-fallback" style="margin:0 auto 0.75rem;"><?= h(initials(Auth::fullName() ?? '')) ?></div>
                        <?php endif; ?>
                        <h3 style="margin-bottom:0;"><?= h(Auth::fullName()) ?></h3>
                        <p class="form-hint" style="margin:0 0 0.75rem;"><?= h($profile['headline'] ?? '') ?></p>
                        <a href="<?= h(base_url('applicant/profile.php')) ?>" class="btn btn-outline btn-block">View Profile</a>
                    </div>

                    <div class="card">
                        <a href="<?= h(base_url('applicant/saved-jobs.php')) ?>" style="display:flex; justify-content:space-between; text-decoration:none; padding:0.4rem 0;">
                            <span>Saved jobs</span> <strong><?= $savedJobsCount ?></strong>
                        </a>
                        <a href="<?= h(base_url('applicant/my-applications.php')) ?>" style="display:flex; justify-content:space-between; text-decoration:none; padding:0.4rem 0;">
                            <span>Applications</span> <strong><?= count(\IMatchBetter\Models\Application::forApplicant($userId)) ?></strong>
                        </a>
                        <a href="<?= h(base_url('applicant/job-preferences.php')) ?>" style="display:flex; justify-content:space-between; text-decoration:none; padding:0.4rem 0;">
                            <span>Preferences</span> <span>&rarr;</span>
                        </a>
                    </div>
                </div>

                <div>
                    <div class="card">
                        <h3 style="margin-bottom:0;">Your career Dashboard</h3>
                        <p class="form-hint">Here are opportunities shaped around your profile.</p>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin:1.25rem 0 0.75rem;">
                        <h3 style="margin:0;">Top job matches</h3>
                        <a href="<?= h(base_url('applicant/recommendations.php')) ?>">See all &rarr;</a>
                    </div>

                    <?php if (empty($topMatches)): ?>
                        <div class="card empty-state">
                            No recommendations yet. <a href="<?= h(base_url('applicant/profile-wizard.php')) ?>">Add your skills</a> to see personalized matches.
                        </div>
                    <?php else: ?>
                        <?php foreach ($topMatches as $job): ?>
                            <div style="position:relative; margin-bottom:1rem;">
                                <span class="badge badge-approved" style="position:absolute; top:0.75rem; right:0.75rem; z-index:1;"><?= (int) $job['match_score'] ?>% Match</span>
                                <?php require __DIR__ . '/../includes/partials/job-card.php'; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div>
                    <div class="card" style="margin-bottom:1.5rem;">
                        <h3>Career Insight</h3>
                        <p>Your match quality improves as you add skills and preferences.</p>
                        <a href="<?= h(base_url('applicant/profile-wizard.php')) ?>">Improve profile &rarr;</a>
                    </div>

                    <div class="card" style="margin-bottom:1.5rem;">
                        <h3>People you may know</h3>
                        <?php if (empty($suggestions)): ?>
                            <p class="empty-state">No suggestions right now.</p>
                        <?php else: ?>
                            <?php foreach ($suggestions as $person): ?>
                                <div style="padding:0.4rem 0;">
                                    <strong><?= h($person['full_name']) ?></strong>
                                    <p class="form-hint" style="margin:0;"><?= h($person['applicant_headline'] ?? $person['company_name'] ?? ucfirst($person['role'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <a href="<?= h(base_url('applicant/network.php')) ?>">View network &rarr;</a>
                    </div>

                    <a href="<?= h(base_url('applicant/notifications.php')) ?>" class="card" style="text-decoration:none; display:block;">
                        <h3 style="margin-bottom:0;">Notifications</h3>
                        <p style="margin:0;"><?= $unreadCount > 0 ? $unreadCount . ' unread notification' . ($unreadCount === 1 ? '' : 's') : "You're all caught up." ?></p>
                    </a>
                </div>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

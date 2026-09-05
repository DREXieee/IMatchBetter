<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\Application;
use IMatchBetter\Models\EmployerProfile;
use IMatchBetter\Models\Interview;
use IMatchBetter\Models\Job;
use IMatchBetter\Models\Notification;
use IMatchBetter\Models\User;
use IMatchBetter\Services\SkillMatchService;

Guard::requireRole('employer');

$employerId = (int) Auth::id();
$profile = EmployerProfile::findByUserId($employerId);
$isApproved = $profile && $profile['approval_status'] === 'approved';
$unreadCount = Notification::unreadCount($employerId);

$openJobsCount = 0;
$applicantsCount = 0;
$topCandidates = [];
$averageMatch = 0;
$upcomingInterviews = [];

if ($isApproved) {
    $jobs = Job::forEmployer($employerId);
    $openJobsCount = count(array_filter($jobs, static fn (array $j) => $j['status'] === 'open'));

    $appCounts = Application::countsByEmployer($employerId);
    foreach ($appCounts as $jobCounts) {
        $applicantsCount += $jobCounts['total'];
    }

    $allMatches = [];
    foreach ($jobs as $job) {
        foreach (SkillMatchService::matchedApplicantsForJob((int) $job['id']) as $match) {
            $match['job_title'] = $job['title'];
            $allMatches[] = $match;
        }
    }
    usort($allMatches, static fn (array $a, array $b) => $b['match_score'] <=> $a['match_score']);

    if (!empty($allMatches)) {
        $averageMatch = (int) round(array_sum(array_column($allMatches, 'match_score')) / count($allMatches));
    }

    foreach (array_slice($allMatches, 0, 3) as $match) {
        $applicant = User::findById((int) $match['applicant_id']);
        if ($applicant) {
            $topCandidates[] = array_merge($match, ['full_name' => $applicant['full_name']]);
        }
    }

    $now = date('Y-m-d H:i:s');
    $upcomingInterviews = array_values(array_filter(
        Interview::forEmployer($employerId),
        static fn (array $i) => $i['scheduled_at'] > $now && !in_array($i['status'], ['cancelled', 'completed', 'declined'], true)
    ));
    usort($upcomingInterviews, static fn (array $a, array $b) => strcmp($a['scheduled_at'], $b['scheduled_at']));
    $upcomingInterviews = array_slice($upcomingInterviews, 0, 3);
}

$role = 'employer';
$pageTitle = 'Employer Dashboard — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Welcome, <?= h(Auth::fullName()) ?></h1>
            <p><?= h($profile['company_name'] ?? '') ?></p>
        </div>

        <?php if (!$isApproved): ?>
            <div class="card" style="border-color: var(--color-warning); margin-bottom:1.5rem;">
                <h3>Your account is pending approval</h3>
                <p>An admin needs to review your company before you can post jobs. You can still edit your company profile in the meantime.</p>
                <a href="<?= h(base_url('employer/pending-approval.php')) ?>" class="btn btn-outline">View status</a>
            </div>
        <?php else: ?>
            <div class="stat-row">
                <div class="stat-card">
                    <div class="stat-value"><?= $openJobsCount ?></div>
                    <div class="stat-label">Open jobs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $applicantsCount ?></div>
                    <div class="stat-label">Applicants</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= count($upcomingInterviews) ?></div>
                    <div class="stat-label">Interviews</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $averageMatch ?>%</div>
                    <div class="stat-label">Average match</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-2" style="margin-top:1.5rem;">
            <a href="<?= h(base_url('employer/company-profile.php')) ?>" class="card" style="text-decoration:none;">
                <h3>Company Profile</h3>
                <p>Update your company info and logo.</p>
            </a>
            <?php if ($isApproved): ?>
            <a href="<?= h(base_url('employer/jobs/index.php')) ?>" class="card" style="text-decoration:none;">
                <h3>My Job Postings</h3>
                <p>Create, edit, and close job listings.</p>
            </a>
            <a href="<?= h(base_url('employer/talent-database.php')) ?>" class="card" style="text-decoration:none;">
                <h3>Graduate Talent Database</h3>
                <p>Search graduates and job seekers by school, degree, and skills.</p>
            </a>
            <?php else: ?>
            <div class="card" style="opacity:0.6;">
                <h3>My Job Postings</h3>
                <p>Available once your account is approved.</p>
            </div>
            <?php endif; ?>
            <a href="<?= h(base_url('employer/notifications.php')) ?>" class="card" style="text-decoration:none;">
                <h3>Notifications</h3>
                <p><?= $unreadCount > 0 ? $unreadCount . ' unread notification' . ($unreadCount === 1 ? '' : 's') : 'You\'re all caught up.' ?></p>
            </a>
        </div>

        <?php if ($isApproved): ?>
        <div class="grid grid-2" style="margin-top:1.5rem; align-items:start;">
            <div class="card">
                <h3>Top Candidates</h3>
                <?php if (empty($topCandidates)): ?>
                    <p class="empty-state">No matched applicants yet.</p>
                <?php else: ?>
                    <?php foreach ($topCandidates as $candidate): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:0.6rem 0; border-bottom:1px solid var(--color-border);">
                            <div>
                                <strong><?= h($candidate['full_name']) ?></strong>
                                <p class="form-hint" style="margin:0;"><?= h($candidate['job_title']) ?></p>
                            </div>
                            <span class="badge badge-approved"><?= (int) $candidate['match_score'] ?>%</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Upcoming Interviews</h3>
                <?php if (empty($upcomingInterviews)): ?>
                    <p class="empty-state">No interviews scheduled.</p>
                <?php else: ?>
                    <?php foreach ($upcomingInterviews as $interview): ?>
                        <div style="padding:0.6rem 0; border-bottom:1px solid var(--color-border);">
                            <strong><?= h($interview['applicant_name']) ?></strong>
                            <p class="form-hint" style="margin:0;">
                                <?= h($interview['job_title']) ?> &middot;
                                <?= h(date('M j, Y g:i A', strtotime($interview['scheduled_at']))) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

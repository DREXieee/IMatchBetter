<?php
/** @var string $role */
$current = ltrim($_SERVER['SCRIPT_NAME'], '/');

$links = match ($role) {
    'applicant' => [
        'dashboard.php' => 'Dashboard',
        'profile.php' => 'My Profile & Resume',
        'network.php' => 'Network',
        'messages.php' => 'Messages',
        'job-preferences.php' => 'Job Preferences',
        'my-applications.php' => 'My Applications',
        'saved-jobs.php' => 'Saved Jobs',
        'recommendations.php' => 'Recommended Jobs',
        'career-growth.php' => 'Career Growth Insights',
        'interviews/index.php' => 'Interviews',
        'notifications.php' => 'Notifications',
        'reviews/index.php' => 'My Reviews',
        'complaints/index.php' => 'Complaints',
    ],
    'employer' => [
        'dashboard.php' => 'Dashboard',
        'company-profile.php' => 'Company Profile',
        'jobs/index.php' => 'My Job Postings',
        'talent-database.php' => 'Graduate Talent Database',
        'network.php' => 'Network',
        'messages.php' => 'Messages',
        'interviews/index.php' => 'Interviews',
        'notifications.php' => 'Notifications',
        'complaints/index.php' => 'Complaints',
    ],
    'admin' => [
        'dashboard.php' => 'Dashboard',
        'employers/pending.php' => 'Employer Approvals',
        'users.php' => 'Manage Users',
        'jobs.php' => 'Moderate Jobs',
        'graduates.php' => 'Graduate Talent Database',
        'reviews/index.php' => 'Review Moderation',
        'complaints/index.php' => 'Complaints',
        'notifications.php' => 'Notifications',
        'reports.php' => 'Reports & Insights',
        'audit-log.php' => 'Audit Log',
    ],
    default => [],
};
?>
<aside class="dashboard-sidebar">
    <?php foreach ($links as $href => $label): ?>
        <a href="<?= h(base_url($role . '/' . $href)) ?>" class="<?= str_ends_with($current, $role . '/' . $href) ? 'active' : '' ?>"><?= h($label) ?></a>
    <?php endforeach; ?>
</aside>

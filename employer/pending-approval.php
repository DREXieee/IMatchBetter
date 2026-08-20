<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\EmployerProfile;

Guard::requireRole('employer');

$profile = EmployerProfile::findByUserId((int) Auth::id());

if ($profile && $profile['approval_status'] === 'approved') {
    redirect('/employer/dashboard.php');
}

$role = 'employer';
$pageTitle = 'Pending Approval — IMatchBetter';
require __DIR__ . '/../includes/header.php';
?>
<main class="container" style="padding: 3rem 0; max-width: 640px;">
    <div class="card">
        <?php if ($profile && $profile['approval_status'] === 'rejected'): ?>
            <h1>Your request was not approved</h1>
            <p>An admin reviewed your employer registration and did not approve it.</p>
            <?php if (!empty($profile['rejection_reason'])): ?>
                <p><strong>Reason:</strong> <?= h($profile['rejection_reason']) ?></p>
            <?php endif; ?>
            <p>Update your company profile and an admin may reconsider.</p>
            <a href="<?= h(base_url('employer/company-profile.php')) ?>" class="btn btn-primary">Edit company profile</a>
        <?php else: ?>
            <h1>Your account is pending approval</h1>
            <p>Thanks for registering <strong><?= h($profile['company_name'] ?? '') ?></strong> with IMatchBetter. An admin will review your request shortly — you'll be able to post jobs as soon as it's approved.</p>
            <p>In the meantime, you can update your company profile.</p>
            <a href="<?= h(base_url('employer/company-profile.php')) ?>" class="btn btn-outline">Edit company profile</a>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>

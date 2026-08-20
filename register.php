<?php

require __DIR__ . '/includes/bootstrap.php';

use IMatchBetter\Auth\Guard;

Guard::requireGuest();

$pageTitle = 'Sign Up — IMatchBetter';
require __DIR__ . '/includes/header.php';
?>
<main class="container" style="padding-top: 3rem; padding-bottom: 3rem;">
    <div style="text-align:center; max-width: 640px; margin: 0 auto 2.5rem;">
        <h1>Join IMatchBetter</h1>
        <p>Choose how you'd like to get started.</p>
    </div>

    <div class="grid grid-2" style="max-width: 760px; margin: 0 auto;">
        <a href="<?= h(base_url('register-applicant.php')) ?>" class="card" style="text-decoration:none;">
            <h3>I'm looking for a job</h3>
            <p>Create a profile, upload your resume, and apply to open roles.</p>
            <span class="btn btn-primary">Sign up as an Applicant</span>
        </a>

        <a href="<?= h(base_url('register-employer.php')) ?>" class="card" style="text-decoration:none;">
            <h3>I'm hiring</h3>
            <p>Register your company. An admin will review and approve your account before you can post jobs.</p>
            <span class="btn btn-outline">Sign up as an Employer</span>
        </a>
    </div>

    <p style="text-align:center; margin-top: 2rem;">Already have an account? <a href="<?= h(base_url('login.php')) ?>">Log in</a></p>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

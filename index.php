<?php

require __DIR__ . '/includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Config\Database;
use IMatchBetter\Models\SavedJob;
use IMatchBetter\Services\JobSearchService;

$featuredJobs = JobSearchService::featured(6);
$savedJobIds = (Auth::check() && Auth::role() === 'applicant') ? SavedJob::savedJobIdSet((int) Auth::id()) : [];

$pdo = Database::connection();
$openJobsCount = (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'open'")->fetchColumn();
$companiesCount = (int) $pdo->query("SELECT COUNT(*) FROM employer_profiles WHERE approval_status = 'approved'")->fetchColumn();
$applicantsCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'applicant' AND is_active = 1")->fetchColumn();
$hiresCount = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'hired'")->fetchColumn();

$pageTitle = 'IMatchBetter — Find Your Next Role';
$extraStylesheets = ['css/landing.css'];
$extraScripts = ['js/scroll-animate.js'];
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container hero-grid">
        <div>
            <h1>Find work you're proud of. Hire people who fit.</h1>
            <p class="lead">IMatchBetter connects job seekers with employers who've been reviewed and approved by our team — no fake listings, no noise.</p>

            <form method="get" action="<?= h(base_url('jobs.php')) ?>" class="hero-search">
                <input class="form-control" type="text" name="q" placeholder="Job title or keyword">
                <input class="form-control" type="text" name="location" placeholder="City or Remote">
                <button type="submit" class="btn btn-primary">Search Jobs</button>
            </form>
        </div>
        <div class="hero-art" data-animate="fade-up">
            IMatch<br>Better
        </div>
    </div>
</section>

<section class="how-it-works">
    <div class="container">
        <h2>How it works</h2>
        <p>Whether you're looking for your next role or your next hire, IMatchBetter keeps it simple.</p>

        <div class="grid grid-3">
            <div class="card step-card" data-animate="fade-up">
                <div class="step-number">1</div>
                <h3>Create your account</h3>
                <p>Sign up as an applicant or register your company as an employer.</p>
            </div>
            <div class="card step-card" data-animate="fade-up">
                <div class="step-number">2</div>
                <h3>Get matched</h3>
                <p>Applicants upload a resume and apply. Employers post jobs once approved by our admin team.</p>
            </div>
            <div class="card step-card" data-animate="fade-up">
                <div class="step-number">3</div>
                <h3>Track progress</h3>
                <p>Follow every application from submitted to hired, all in one dashboard.</p>
            </div>
        </div>
    </div>
</section>

<section class="featured-jobs">
    <div class="container">
        <div class="section-header">
            <h2 style="margin:0;">Recently posted jobs</h2>
            <a href="<?= h(base_url('jobs.php')) ?>" class="btn btn-outline">View All Jobs</a>
        </div>

        <?php if (empty($featuredJobs)): ?>
            <div class="card empty-state">No open jobs yet — check back soon.</div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($featuredJobs as $job): ?>
                    <?php require __DIR__ . '/includes/partials/job-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="stats-strip">
    <div class="container">
        <div class="grid grid-4">
            <div data-animate="fade-up">
                <div class="stat-number"><?= $openJobsCount ?></div>
                <div class="stat-caption">Open Jobs</div>
            </div>
            <div data-animate="fade-up">
                <div class="stat-number"><?= $companiesCount ?></div>
                <div class="stat-caption">Approved Companies</div>
            </div>
            <div data-animate="fade-up">
                <div class="stat-number"><?= $applicantsCount ?></div>
                <div class="stat-caption">Job Seekers</div>
            </div>
            <div data-animate="fade-up">
                <div class="stat-number"><?= $hiresCount ?></div>
                <div class="stat-caption">Successful Hires</div>
            </div>
        </div>
    </div>
</section>

<section class="employer-cta" data-animate="fade-up">
    <div class="container">
        <h2>Hiring? Get in front of real candidates.</h2>
        <p>Register your company, get approved by our team, and start posting jobs in minutes.</p>
        <a href="<?= h(base_url('register-employer.php')) ?>" class="btn btn-primary">Register as an Employer</a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

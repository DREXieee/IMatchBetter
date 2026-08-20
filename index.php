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
$bodyClass = 'landing-page';
$extraStylesheets = ['css/landing.css'];
$extraScripts = ['js/scroll-animate.js'];
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="hero-mesh" aria-hidden="true">
        <div class="mesh-blob mesh-blob-1"></div>
        <div class="mesh-blob mesh-blob-2"></div>
        <div class="mesh-blob mesh-blob-3"></div>
    </div>
    <div class="container hero-grid">
        <div data-animate="fade-up">
            <span class="eyebrow-pill">Reviewed employers. Real listings.</span>
            <h1>Find work you're <span class="text-gradient">proud of</span>. Hire people who <span class="text-gradient">fit</span>.</h1>
            <p class="lead">IMatchBetter connects job seekers with employers who've been reviewed and approved by our team — no fake listings, no noise.</p>

            <form method="get" action="<?= h(base_url('jobs.php')) ?>" class="hero-search">
                <div class="hero-search-field">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" name="q" placeholder="Job title or keyword" aria-label="Job title or keyword">
                </div>
                <div class="hero-search-divider" aria-hidden="true"></div>
                <div class="hero-search-field">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    <input type="text" name="location" placeholder="City or Remote" aria-label="City or Remote">
                </div>
                <button type="submit" class="btn btn-hero-search">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    Search Jobs
                </button>
            </form>
        </div>

        <div class="hero-visual" data-animate="fade-up">
            <div class="hero-visual-panel">
                <div class="hero-visual-panel-row">
                    <span class="hero-visual-avatar">JP</span>
                    <div>
                        <strong>Junior Web Developer</strong>
                        <span>Manila, Philippines · Full-time</span>
                    </div>
                </div>
                <div class="hero-visual-panel-row">
                    <span class="hero-visual-avatar hero-visual-avatar-alt">AR</span>
                    <div>
                        <strong>Acme Robotics</strong>
                        <span>Verified employer</span>
                    </div>
                </div>
                <div class="hero-visual-skills">
                    <span class="hero-visual-tag">HTML</span>
                    <span class="hero-visual-tag">CSS</span>
                    <span class="hero-visual-tag">JavaScript</span>
                </div>
            </div>

            <div class="hero-visual-badge">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                <div>
                    <strong>98%</strong>
                    <span>Match Found</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="how-it-works">
    <div class="container">
        <h2 data-animate="fade-up">How it works</h2>
        <p data-animate="fade-up">Whether you're looking for your next role or your next hire, IMatchBetter keeps it simple.</p>

        <div class="grid grid-3">
            <div class="card step-card" data-animate="fade-up">
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                </div>
                <h3>Create your account</h3>
                <p>Sign up as an applicant or register your company as an employer.</p>
            </div>
            <div class="card step-card" data-animate="fade-up">
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/></svg>
                </div>
                <h3>Get matched</h3>
                <p>Applicants upload a resume and apply. Employers post jobs once approved by our admin team.</p>
            </div>
            <div class="card step-card" data-animate="fade-up">
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="M7 15v3M12 10v8M17 6v12"/></svg>
                </div>
                <h3>Track progress</h3>
                <p>Follow every application from submitted to hired, all in one dashboard.</p>
            </div>
        </div>
    </div>
</section>

<section class="featured-jobs">
    <div class="container">
        <div class="section-header" data-animate="fade-up">
            <h2 style="margin:0;">Recently posted jobs</h2>
            <a href="<?= h(base_url('jobs.php')) ?>" class="btn btn-view-all">View All Jobs</a>
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
        <div class="stats-pill" data-animate="fade-up">
            <div class="stat-block">
                <div class="stat-number"><?= $openJobsCount ?></div>
                <div class="stat-caption">Open Jobs</div>
            </div>
            <div class="stat-block">
                <div class="stat-number"><?= $companiesCount ?></div>
                <div class="stat-caption">Approved Companies</div>
            </div>
            <div class="stat-block">
                <div class="stat-number"><?= $applicantsCount ?></div>
                <div class="stat-caption">Job Seekers</div>
            </div>
            <div class="stat-block">
                <div class="stat-number"><?= $hiresCount ?></div>
                <div class="stat-caption">Successful Hires</div>
            </div>
        </div>
    </div>
</section>

<section class="employer-cta-section">
    <div class="container">
        <div class="employer-cta" data-animate="fade-up">
            <div class="employer-cta-mesh" aria-hidden="true"></div>
            <div class="employer-cta-content">
                <h2>Hiring? Get in front of real candidates.</h2>
                <p>Register your company, get approved by our team, and start posting jobs in minutes.</p>
                <a href="<?= h(base_url('register-employer.php')) ?>" class="btn btn-primary btn-glow">Register as an Employer</a>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

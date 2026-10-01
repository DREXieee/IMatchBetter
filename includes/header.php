<?php

use IMatchBetter\Auth\Auth;
use IMatchBetter\Models\Notification;

$pageTitle = $pageTitle ?? 'IMatchBetter';
$metaDescription = $metaDescription ?? "IMatchBetter connects job seekers with employers who've been reviewed and approved by our team — no fake listings, no noise.";
$unreadNotifications = Auth::check() ? Notification::unreadCount((int) Auth::id()) : 0;

$currentUrl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
$logoUrl = base_url('img/logo.png');
$isDashboard = isset($role) && Auth::check();
$dashboardLinks = [];
if ($isDashboard) {
    require BASE_PATH . '/includes/partials/dashboard-nav-links.php';
}
$currentScript = ltrim($_SERVER['SCRIPT_NAME'], '/');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?></title>
    <meta name="description" content="<?= h($metaDescription) ?>">
    <link rel="icon" type="image/png" href="<?= h($logoUrl) ?>">
    <link rel="apple-touch-icon" href="<?= h($logoUrl) ?>">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="IMatchBetter">
    <meta property="og:title" content="<?= h($pageTitle) ?>">
    <meta property="og:description" content="<?= h($metaDescription) ?>">
    <meta property="og:url" content="<?= h($currentUrl) ?>">
    <meta property="og:image" content="<?= h($logoUrl) ?>">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= h($pageTitle) ?>">
    <meta name="twitter:description" content="<?= h($metaDescription) ?>">
    <meta name="twitter:image" content="<?= h($logoUrl) ?>">

    <link rel="stylesheet" href="<?= h(base_url('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= h(base_url('css/layout.css')) ?>">
    <link rel="stylesheet" href="<?= h(base_url('css/components.css')) ?>">
    <link rel="stylesheet" href="<?= h(base_url('css/logo-flap.css')) ?>">
    <link rel="stylesheet" href="<?= h(base_url('css/loading-screen.css')) ?>">
    <!-- Safe-by-default fallback: if page-transition.css fails to load for any reason, this
         keeps the overlay display:none instead of rendering as an unstyled full-width block.
         An ID selector so it matches page-transition.css's own #page-transition-overlay rule in
         specificity — that rule (defined later, so it wins on cascade order) flips it back to
         display:flex once the stylesheet is actually in effect. -->
    <style>#page-transition-overlay{display:none;}</style>
    <link rel="stylesheet" href="<?= h(base_url('css/page-transition.css')) ?>">
    <?php if (!empty($extraStylesheets)): foreach ($extraStylesheets as $sheet): ?>
    <link rel="stylesheet" href="<?= h(base_url($sheet)) ?>">
    <?php endforeach; endif; ?>
</head>
<body<?= !empty($bodyClass) ? ' class="' . h($bodyClass) . '"' : '' ?>>
<div id="page-transition-overlay" class="page-transition-overlay" aria-hidden="true">
    <div class="logo-flap-stage">
        <svg class="logo-flap" viewBox="0 0 200 220" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="gradBlueLight" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#3fa0e8"/>
                    <stop offset="100%" stop-color="#1a5fc4"/>
                </linearGradient>
                <linearGradient id="gradBlueDark" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#1c5bb0"/>
                    <stop offset="100%" stop-color="#123c7a"/>
                </linearGradient>
                <linearGradient id="gradTealLight" x1="100%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#3fd6b8"/>
                    <stop offset="100%" stop-color="#0f9e86"/>
                </linearGradient>
                <linearGradient id="gradTealDark" x1="100%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#149280"/>
                    <stop offset="100%" stop-color="#0a6656"/>
                </linearGradient>
            </defs>
            <g class="wing wing-left">
                <path d="M100,92 C 72,44 32,28 14,52 C -3,78 6,122 43,133 C 66,140 90,122 100,92 Z" fill="url(#gradBlueLight)"/>
                <path d="M100,98 C 74,112 38,110 27,142 C 16,174 38,198 70,192 C 92,188 100,150 100,98 Z" fill="url(#gradBlueDark)"/>
            </g>
            <g class="wing wing-right">
                <path d="M100,92 C 128,44 168,28 186,52 C 203,78 194,122 157,133 C 134,140 110,122 100,92 Z" fill="url(#gradTealLight)"/>
                <path d="M100,98 C 126,112 162,110 173,142 C 184,174 162,198 130,192 C 108,188 100,150 100,98 Z" fill="url(#gradTealDark)"/>
            </g>
            <ellipse cx="100" cy="118" rx="14" ry="22" fill="#0b2a4d" opacity="0.18"/>
            <g class="logo-dots">
                <circle cx="87" cy="38" r="8.5" fill="#12305c"/>
                <circle cx="113" cy="38" r="8.5" fill="#12305c"/>
            </g>
        </svg>
    </div>
</div>
<!-- Shown unconditionally on every page load (js/page-transition.js fades it out shortly
     after) — no longer tied to link clicks/navigation timing. It used to intercept clicks
     and delay navigation to show this before unloading, but that left it stuck permanently
     visible whenever the outgoing page got frozen into the browser's back/forward cache
     mid-transition; pressing Back then restored that frozen, still-visible, click-blocking
     overlay. Reacting only to this page's own load avoids that class of bug entirely. -->
<noscript><style>#page-transition-overlay{display:none!important;}</style></noscript>
<script>
(function () {
    var el = document.getElementById('page-transition-overlay');
    if (el) el.classList.add('is-active');
})();
</script>
<?php require BASE_PATH . '/includes/partials/loading-screen.php'; ?>
<?php if ($isDashboard): ?>
<header class="site-header site-header--dashboard">
    <div class="container">
        <div class="logo-menu">
            <button type="button" class="brand brand-menu-toggle" data-logo-menu-toggle aria-haspopup="true" aria-expanded="false">
                <img src="<?= h($logoUrl) ?>" alt="" width="28" height="28" class="brand-logo">
                IMatch<span>Better</span>
            </button>
            <nav class="logo-menu-panel" data-logo-menu aria-label="Main menu">
                <?php foreach ($dashboardLinks as $href => $label): ?>
                    <a href="<?= h(base_url($role . '/' . $href)) ?>" class="<?= str_ends_with($currentScript, $role . '/' . $href) ? 'active' : '' ?>">
                        <?= h($label) ?>
                        <?php if ($href === 'notifications.php' && $unreadNotifications > 0): ?>
                            <span class="badge badge-rejected"><?= $unreadNotifications > 9 ? '9+' : $unreadNotifications ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <a href="<?= h(base_url('logout.php')) ?>" class="btn btn-primary">Log Out</a>
    </div>
</header>
<?php else: ?>
<header class="site-header">
    <div class="container">
        <a href="<?= h(base_url('index.php')) ?>" class="brand">
            <img src="<?= h($logoUrl) ?>" alt="" width="28" height="28" class="brand-logo">
            IMatch<span>Better</span>
        </a>

        <button type="button" class="nav-toggle" data-nav-toggle aria-label="Toggle navigation" aria-expanded="false">
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
        </button>

        <nav class="main-nav" data-nav>
            <a href="<?= h(base_url('jobs.php')) ?>">Find Jobs</a>
            <?php if (!Auth::check()): ?>
                <a href="<?= h(base_url('register-employer.php')) ?>">For Employers</a>
            <?php endif; ?>

            <div class="nav-actions">
                <?php if (Auth::check()): ?>
                    <a href="<?= h(base_url(Auth::role() . '/notifications.php')) ?>" class="btn btn-outline" style="position:relative;">
                        Notifications
                        <?php if ($unreadNotifications > 0): ?>
                            <span class="badge badge-rejected" style="position:absolute; top:-8px; right:-8px; padding:0.1rem 0.4rem; font-size:0.7rem;"><?= $unreadNotifications > 9 ? '9+' : $unreadNotifications ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= h(base_url(Auth::dashboardPathForRole((string) Auth::role()))) ?>" class="btn btn-outline">Dashboard</a>
                    <a href="<?= h(base_url('logout.php')) ?>" class="btn btn-primary">Log Out</a>
                <?php else: ?>
                    <a href="<?= h(base_url('auth.php?tab=login')) ?>" class="btn btn-outline">Log In</a>
                    <a href="<?= h(base_url('auth.php?tab=signup')) ?>" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
<?php endif; ?>
<?php require BASE_PATH . '/includes/flash.php'; ?>

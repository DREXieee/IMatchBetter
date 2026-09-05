<?php

use IMatchBetter\Auth\Auth;
use IMatchBetter\Models\Notification;

$pageTitle = $pageTitle ?? 'IMatchBetter';
$metaDescription = $metaDescription ?? "IMatchBetter connects job seekers with employers who've been reviewed and approved by our team — no fake listings, no noise.";
$unreadNotifications = Auth::check() ? Notification::unreadCount((int) Auth::id()) : 0;

$currentUrl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
$logoUrl = base_url('img/logo.png');
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
    <link rel="stylesheet" href="<?= h(base_url('css/loading-screen.css')) ?>">
    <?php if (!empty($extraStylesheets)): foreach ($extraStylesheets as $sheet): ?>
    <link rel="stylesheet" href="<?= h(base_url($sheet)) ?>">
    <?php endforeach; endif; ?>
</head>
<body<?= !empty($bodyClass) ? ' class="' . h($bodyClass) . '"' : '' ?>>
<?php require BASE_PATH . '/includes/partials/loading-screen.php'; ?>
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
<?php require BASE_PATH . '/includes/flash.php'; ?>

<?php

use IMatchBetter\Auth\Auth;
use IMatchBetter\Models\Notification;

$pageTitle = $pageTitle ?? 'IMatchBetter';
$unreadNotifications = Auth::check() ? Notification::unreadCount((int) Auth::id()) : 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= h(base_url('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= h(base_url('css/layout.css')) ?>">
    <link rel="stylesheet" href="<?= h(base_url('css/components.css')) ?>">
    <?php if (!empty($extraStylesheets)): foreach ($extraStylesheets as $sheet): ?>
    <link rel="stylesheet" href="<?= h(base_url($sheet)) ?>">
    <?php endforeach; endif; ?>
</head>
<body<?= !empty($bodyClass) ? ' class="' . h($bodyClass) . '"' : '' ?>>
<header class="site-header">
    <div class="container">
        <a href="<?= h(base_url('index.php')) ?>" class="brand">IMatch<span>Better</span></a>

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
                    <a href="<?= h(base_url('login.php')) ?>" class="btn btn-outline">Log In</a>
                    <a href="<?= h(base_url('register.php')) ?>" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
<?php require BASE_PATH . '/includes/flash.php'; ?>

<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\EmployerReview;

Guard::requireRole('applicant');

$reviews = EmployerReview::forApplicant((int) Auth::id());

$role = 'applicant';
$pageTitle = 'My Reviews — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1rem;">
            <h1 style="margin:0;">My Reviews</h1>
            <a href="<?= h(base_url('applicant/reviews/create.php')) ?>" class="btn btn-primary">Write a Review</a>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="card empty-state">You haven't reviewed any employers yet.</div>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="card" style="margin-bottom:1rem; max-width:640px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem;">
                        <div>
                            <h3 style="margin-bottom:0.25rem;"><?= h($review['company_name']) ?></h3>
                            <p class="star-rating" style="margin-bottom:0.25rem;"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?></p>
                            <?php if (!empty($review['title'])): ?><p style="font-weight:600; margin-bottom:0.25rem;"><?= h($review['title']) ?></p><?php endif; ?>
                            <p><?= h($review['body']) ?></p>
                        </div>
                        <?php $status = $review['status']; require __DIR__ . '/../../includes/partials/status-badge.php'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

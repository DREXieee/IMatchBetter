<?php

require __DIR__ . '/../../includes/bootstrap.php';

use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\ApplicantReview;
use IMatchBetter\Models\EmployerReview;

Guard::requireRole('admin');

$pendingEmployerReviews = EmployerReview::pending();
$pendingApplicantReviews = ApplicantReview::pending();

$role = 'admin';
$pageTitle = 'Review Moderation — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Review Moderation</h1>
            <p>Approve or reject reviews before they go live.</p>
        </div>

        <h3>Applicant &rarr; Employer reviews</h3>
        <?php if (empty($pendingEmployerReviews)): ?>
            <div class="card empty-state">Nothing pending.</div>
        <?php else: ?>
            <?php foreach ($pendingEmployerReviews as $review): ?>
                <div class="card" style="margin-bottom:1rem;">
                    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                        <div>
                            <p style="margin-bottom:0.25rem;"><strong><?= h($review['applicant_name']) ?></strong> about <strong><?= h($review['company_name']) ?></strong></p>
                            <p class="star-rating" style="margin-bottom:0.25rem;"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?></p>
                            <?php if (!empty($review['title'])): ?><p style="font-weight:600; margin-bottom:0.25rem;"><?= h($review['title']) ?></p><?php endif; ?>
                            <p><?= h($review['body']) ?></p>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:0.5rem; min-width:140px;">
                            <form method="post" action="<?= h(base_url('admin/reviews/moderate.php')) ?>">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="type" value="employer">
                                <input type="hidden" name="id" value="<?= (int) $review['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-primary btn-block">Approve</button>
                            </form>
                            <form method="post" action="<?= h(base_url('admin/reviews/moderate.php')) ?>">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="type" value="employer">
                                <input type="hidden" name="id" value="<?= (int) $review['id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-outline btn-block">Reject</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <h3 style="margin-top:2rem;">Employer &rarr; Applicant reviews</h3>
        <?php if (empty($pendingApplicantReviews)): ?>
            <div class="card empty-state">Nothing pending.</div>
        <?php else: ?>
            <?php foreach ($pendingApplicantReviews as $review): ?>
                <div class="card" style="margin-bottom:1rem;">
                    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                        <div>
                            <p style="margin-bottom:0.25rem;"><strong><?= h($review['company_name']) ?></strong> about <strong><?= h($review['applicant_name']) ?></strong></p>
                            <p class="star-rating" style="margin-bottom:0.25rem;"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?></p>
                            <?php if (!empty($review['title'])): ?><p style="font-weight:600; margin-bottom:0.25rem;"><?= h($review['title']) ?></p><?php endif; ?>
                            <p><?= h($review['body']) ?></p>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:0.5rem; min-width:140px;">
                            <form method="post" action="<?= h(base_url('admin/reviews/moderate.php')) ?>">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="type" value="applicant">
                                <input type="hidden" name="id" value="<?= (int) $review['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-primary btn-block">Approve</button>
                            </form>
                            <form method="post" action="<?= h(base_url('admin/reviews/moderate.php')) ?>">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="type" value="applicant">
                                <input type="hidden" name="id" value="<?= (int) $review['id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-outline btn-block">Reject</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

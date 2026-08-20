<?php

require __DIR__ . '/includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Models\Job;
use IMatchBetter\Models\SavedJob;
use IMatchBetter\Services\JobSearchService;

$filters = [
    'q' => $_GET['q'] ?? '',
    'location' => $_GET['location'] ?? '',
    'type' => $_GET['type'] ?? '',
    'category' => $_GET['category'] ?? '',
    'careerGrowth' => $_GET['careerGrowth'] ?? '',
    'page' => $_GET['page'] ?? 1,
];

$results = JobSearchService::search($filters);
$employmentTypes = ['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'internship' => 'Internship', 'remote' => 'Remote'];
$categories = Job::distinctCategories();
$savedJobIds = (Auth::check() && Auth::role() === 'applicant') ? SavedJob::savedJobIdSet((int) Auth::id()) : [];

$pageTitle = 'Find Jobs — IMatchBetter';
require __DIR__ . '/includes/header.php';
?>
<main class="container" style="padding: 2.5rem 0;">
    <h1>Find your next role</h1>

    <form method="get" action="<?= h(base_url('jobs.php')) ?>" class="card" style="margin-bottom: 2rem;">
        <div class="grid grid-4">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="q">Keyword</label>
                <input class="form-control" type="text" id="q" name="q" value="<?= h($filters['q']) ?>" placeholder="Job title or keyword">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="location">Location</label>
                <input class="form-control" type="text" id="location" name="location" value="<?= h($filters['location']) ?>" placeholder="City or Remote">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="type">Type</label>
                <select class="form-control" id="type" name="type">
                    <option value="">Any</option>
                    <?php foreach ($employmentTypes as $value => $label): ?>
                        <option value="<?= h($value) ?>" <?= $filters['type'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="category">Category</label>
                <select class="form-control" id="category" name="category">
                    <option value="">Any</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= h($category) ?>" <?= $filters['category'] === $category ? 'selected' : '' ?>><?= h($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid grid-4" style="margin-top:1rem;">
            <div class="form-group" style="margin-bottom:0; display:flex; align-items:center; gap:0.5rem;">
                <input type="checkbox" id="careerGrowth" name="careerGrowth" value="1" <?= $filters['careerGrowth'] ? 'checked' : '' ?>>
                <label class="form-label" for="careerGrowth" style="margin-bottom:0;">Offers career growth / training</label>
            </div>
            <div class="form-group" style="margin-bottom:0; display:flex; align-items:flex-end;">
                <button type="submit" class="btn btn-primary btn-block">Search</button>
            </div>
        </div>
    </form>

    <p class="form-hint"><?= $results['total'] ?> job<?= $results['total'] === 1 ? '' : 's' ?> found</p>

    <?php if (empty($results['jobs'])): ?>
        <div class="card empty-state">No jobs match your search. Try broadening your filters.</div>
    <?php else: ?>
        <div class="grid grid-3">
            <?php foreach ($results['jobs'] as $job): ?>
                <?php require __DIR__ . '/includes/partials/job-card.php'; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($results['totalPages'] > 1): ?>
            <div style="display:flex; justify-content:center; gap:0.5rem; margin-top:2rem;">
                <?php for ($p = 1; $p <= $results['totalPages']; $p++): ?>
                    <?php
                        $query = array_merge($_GET, ['page' => $p]);
                        $url = base_url('jobs.php?' . http_build_query($query));
                    ?>
                    <a href="<?= h($url) ?>" class="btn <?= $p === $results['page'] ? 'btn-primary' : 'btn-outline' ?>" style="min-width:44px; padding:0.6rem;"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

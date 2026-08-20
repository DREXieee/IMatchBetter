<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\ApplicantProfile;

Guard::requireApproved();

$filters = [
    'school' => trim($_GET['school'] ?? ''),
    'degree' => trim($_GET['degree'] ?? ''),
    'field_of_study' => trim($_GET['field_of_study'] ?? ''),
    'graduation_year' => trim($_GET['graduation_year'] ?? ''),
    'skills' => trim($_GET['skills'] ?? ''),
    'location' => trim($_GET['location'] ?? ''),
    'page' => (int) ($_GET['page'] ?? 1),
];

$hasSearched = array_filter($filters, static fn ($v, $k) => $k !== 'page' && $v !== '', ARRAY_FILTER_USE_BOTH) !== [];
$results = ApplicantProfile::searchGraduates($filters);

$role = 'employer';
$pageTitle = 'Graduate Talent Database — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>Graduate Talent Database</h1>
        <p>Search graduates and job seekers who've made their profile visible to employers.</p>

        <form method="get" action="<?= h(base_url('employer/talent-database.php')) ?>" class="card" style="margin-bottom:1.5rem;">
            <div class="grid grid-3">
                <div class="form-group">
                    <label class="form-label" for="school">School</label>
                    <input class="form-control" type="text" id="school" name="school" value="<?= h($filters['school']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="degree">Degree</label>
                    <input class="form-control" type="text" id="degree" name="degree" value="<?= h($filters['degree']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="field_of_study">Field of study</label>
                    <input class="form-control" type="text" id="field_of_study" name="field_of_study" value="<?= h($filters['field_of_study']) ?>">
                </div>
            </div>
            <div class="grid grid-3">
                <div class="form-group">
                    <label class="form-label" for="graduation_year">Graduation year</label>
                    <input class="form-control" type="number" id="graduation_year" name="graduation_year" value="<?= h($filters['graduation_year']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="skills">Skills (comma-separated)</label>
                    <input class="form-control" type="text" id="skills" name="skills" value="<?= h($filters['skills']) ?>" placeholder="PHP, MySQL">
                    <p class="form-hint">Matches candidates tagged with all listed skills.</p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="location">Location</label>
                    <input class="form-control" type="text" id="location" name="location" value="<?= h($filters['location']) ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <?php if (empty($results['graduates'])): ?>
            <div class="card empty-state"><?= $hasSearched ? 'No matching profiles found.' : 'Use the filters above to search the talent database.' ?></div>
        <?php else: ?>
            <p class="form-hint"><?= $results['total'] ?> profile<?= $results['total'] === 1 ? '' : 's' ?> found.</p>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Name</th><th>Headline</th><th>School</th><th>Degree</th><th>Grad. Year</th><th>Skills</th><th>Location</th></tr></thead>
                    <tbody>
                    <?php foreach ($results['graduates'] as $r): ?>
                        <tr>
                            <td><?= h($r['full_name']) ?></td>
                            <td><?= h($r['headline'] ?? '') ?></td>
                            <td><?= h($r['school'] ?? '') ?></td>
                            <td><?= h($r['degree'] ?? '') ?></td>
                            <td><?= h((string) ($r['graduation_year'] ?? '')) ?></td>
                            <td><?= h($r['skills'] ?? '') ?></td>
                            <td><?= h($r['location'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($results['totalPages'] > 1): ?>
                <div style="display:flex; justify-content:center; gap:0.5rem; margin-top:2rem;">
                    <?php for ($p = 1; $p <= $results['totalPages']; $p++): ?>
                        <?php $query = array_merge($_GET, ['page' => $p]); ?>
                        <a href="<?= h(base_url('employer/talent-database.php?' . http_build_query($query))) ?>" class="btn <?= $p === $results['page'] ? 'btn-primary' : 'btn-outline' ?>" style="min-width:44px; padding:0.6rem;"><?= $p ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

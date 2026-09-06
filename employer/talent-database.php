<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Csrf;
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

            <div class="grid grid-3">
                <?php foreach ($results['graduates'] as $r): ?>
                    <div class="card person-card" style="align-items:stretch; text-align:left;">
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <?php if (!empty($r['photo_path'])): ?>
                                <img class="avatar" alt="" src="<?= h(base_url('download.php?photo=' . basename($r['photo_path']))) ?>">
                            <?php else: ?>
                                <div class="avatar avatar-fallback"><?= h(initials($r['full_name'])) ?></div>
                            <?php endif; ?>
                            <div>
                                <p class="person-card-name" style="margin:0;"><?= h($r['full_name']) ?></p>
                                <p class="person-card-subtitle" style="margin:0;"><?= h($r['headline'] ?? '') ?> <?= !empty($r['headline']) && !empty($r['school']) ? '·' : '' ?> <?= h($r['school'] ?? '') ?></p>
                            </div>
                        </div>
                        <?php if (!empty($r['skills'])): ?>
                            <div class="chip-static-list" style="margin-top:0.5rem;">
                                <?php foreach (array_slice(\IMatchBetter\Models\Skill::parseList($r['skills']), 0, 4) as $skillName): ?>
                                    <span class="chip-static"><?= h($skillName) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <p class="form-hint" style="margin:0.5rem 0 0;">
                            <?= h($r['degree'] ?? '') ?><?= !empty($r['degree']) && !empty($r['graduation_year']) ? ' · ' : '' ?><?= !empty($r['graduation_year']) ? 'Graduated ' . h((string) $r['graduation_year']) : '' ?>
                            <?php if (!empty($r['location'])): ?><br><?= h($r['location']) ?><?php endif; ?>
                        </p>
                        <form method="post" action="<?= h(base_url('employer/talent-database/invite.php')) ?>" style="margin-top:0.75rem;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="applicant_id" value="<?= (int) $r['user_id'] ?>">
                            <button type="submit" class="btn btn-primary btn-block">Invite</button>
                        </form>
                    </div>
                <?php endforeach; ?>
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

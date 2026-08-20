<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\ApplicantProfile;
use IMatchBetter\Services\AuditLogger;

Guard::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (in_array($action, ['hide', 'show'], true) && $userId > 0) {
        ApplicantProfile::setVisibility($userId, $action === 'show');
        AuditLogger::log((int) Auth::id(), 'graduate_profile_' . $action, 'applicant_profile', $userId);
        flash('success', 'Profile visibility updated.');
    }

    redirect('/admin/graduates.php');
}

$page = (int) ($_GET['page'] ?? 1);
$results = ApplicantProfile::allWithEducation(['page' => $page]);
$graduates = $results['graduates'];

$role = 'admin';
$pageTitle = 'Graduate Talent Database — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>Graduate Talent Database</h1>
        <p>Applicant profiles with education info filled in. Hide a profile to remove it from employer searches.</p>

        <?php if (empty($graduates)): ?>
            <div class="card empty-state">No graduate profiles yet.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Name</th><th>School</th><th>Degree</th><th>Grad. Year</th><th>Visibility</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($graduates as $g): ?>
                        <tr>
                            <td><?= h($g['full_name']) ?><br><span class="form-hint"><?= h($g['email']) ?></span></td>
                            <td><?= h($g['school'] ?? '') ?></td>
                            <td><?= h($g['degree'] ?? '') ?></td>
                            <td><?= h((string) ($g['graduation_year'] ?? '')) ?></td>
                            <td><span class="badge badge-<?= $g['profile_visibility'] ? 'approved' : 'rejected' ?>"><?= $g['profile_visibility'] ? 'visible' : 'hidden' ?></span></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="user_id" value="<?= (int) $g['user_id'] ?>">
                                    <input type="hidden" name="action" value="<?= $g['profile_visibility'] ? 'hide' : 'show' ?>">
                                    <button type="submit" class="btn btn-outline" style="padding:0.2rem 0.6rem; min-height:auto;"><?= $g['profile_visibility'] ? 'Hide' : 'Show' ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($results['totalPages'] > 1): ?>
                <div style="display:flex; justify-content:center; gap:0.5rem; margin-top:2rem;">
                    <?php for ($p = 1; $p <= $results['totalPages']; $p++): ?>
                        <a href="<?= h(base_url('admin/graduates.php?page=' . $p)) ?>" class="btn <?= $p === $results['page'] ? 'btn-primary' : 'btn-outline' ?>" style="min-width:44px; padding:0.6rem;"><?= $p ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

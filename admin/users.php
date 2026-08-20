<?php

require __DIR__ . '/../includes/bootstrap.php';

use IMatchBetter\Auth\Auth;
use IMatchBetter\Auth\Csrf;
use IMatchBetter\Auth\Guard;
use IMatchBetter\Models\User;
use IMatchBetter\Services\AuditLogger;

Guard::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyRequestOrFail();

    $targetId = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($targetId === (int) Auth::id()) {
        flash('error', 'You cannot deactivate your own account.');
    } elseif (in_array($action, ['activate', 'deactivate'], true)) {
        $target = User::findById($targetId);
        if ($target) {
            User::setActive($targetId, $action === 'activate');
            AuditLogger::log((int) Auth::id(), 'user_' . $action . 'd', 'user', $targetId, $target['email']);
            flash('success', 'User ' . ($action === 'activate' ? 'activated' : 'deactivated') . '.');
        }
    }

    redirect('/admin/users.php');
}

$users = User::all(200, 0);

$role = 'admin';
$pageTitle = 'Manage Users — IMatchBetter';
$extraStylesheets = ['css/dashboard.css'];
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../includes/partials/sidebar-nav.php'; ?>
    <main class="dashboard-main">
        <h1>Manage Users</h1>

        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= h($u['full_name']) ?></td>
                        <td><?= h($u['email']) ?></td>
                        <td><?= h(ucfirst($u['role'])) ?></td>
                        <td><span class="badge badge-<?= $u['is_active'] ? 'approved' : 'rejected' ?>"><?= $u['is_active'] ? 'active' : 'inactive' ?></span></td>
                        <td><?= h(date('M j, Y', strtotime($u['created_at']))) ?></td>
                        <td>
                            <?php if ((int) $u['id'] !== (int) Auth::id()): ?>
                                <form method="post" style="display:inline;">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <input type="hidden" name="action" value="<?= $u['is_active'] ? 'deactivate' : 'activate' ?>">
                                    <button type="submit" class="btn btn-outline" style="padding:0.2rem 0.6rem; min-height:auto;"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

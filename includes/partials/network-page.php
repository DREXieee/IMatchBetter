<?php
/**
 * @var int $userId
 * @var string $role
 * @var array $pendingIncoming
 * @var array $accepted
 * @var array $suggestions
 */

use IMatchBetter\Auth\Csrf;

?>
<h1>Grow your network</h1>
<p>Connect with peers, mentors, and recruiters</p>

<?php if (!empty($pendingIncoming)): ?>
    <h3>Pending requests</h3>
    <div class="grid grid-3" style="margin-bottom:1.5rem;">
        <?php foreach ($pendingIncoming as $request): ?>
            <div class="card person-card">
                <div class="avatar avatar-lg"
                    <?php if (!empty($request['photo_path'])): ?>
                        style="background-image:url('<?= h(base_url('download.php?photo=' . basename($request['photo_path']))) ?>'); background-size:cover;"
                    <?php endif; ?>
                ></div>
                <p class="person-card-name"><?= h($request['other_name']) ?></p>
                <p class="person-card-subtitle"><?= h($request['other_headline'] ?? $request['other_company'] ?? ucfirst($request['other_role'])) ?></p>
                <div style="display:flex; gap:0.5rem;">
                    <form method="post" action="<?= h(base_url('network/respond.php')) ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="connection_id" value="<?= (int) $request['id'] ?>">
                        <input type="hidden" name="accept" value="1">
                        <button type="submit" class="btn btn-primary">Accept</button>
                    </form>
                    <form method="post" action="<?= h(base_url('network/respond.php')) ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="connection_id" value="<?= (int) $request['id'] ?>">
                        <button type="submit" class="btn btn-outline">Decline</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($accepted)): ?>
    <h3>Your connections</h3>
    <div class="grid grid-3" style="margin-bottom:1.5rem;">
        <?php foreach ($accepted as $connection): ?>
            <div class="card person-card">
                <div class="avatar avatar-lg"></div>
                <p class="person-card-name"><?= h($connection['other_name']) ?></p>
                <p class="person-card-subtitle"><?= h($connection['other_headline'] ?? $connection['other_company'] ?? ucfirst($connection['other_role'])) ?></p>
                <a href="<?= h(base_url($role . '/messages.php?with=' . (int) $connection['other_id'])) ?>" class="btn btn-outline">Message</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<h3>People you may know</h3>
<?php if (empty($suggestions)): ?>
    <div class="card empty-state">No suggestions right now — check back soon.</div>
<?php else: ?>
    <div class="grid grid-3">
        <?php foreach ($suggestions as $person): ?>
            <div class="card person-card">
                <div class="avatar avatar-lg"
                    <?php if (!empty($person['photo_path'])): ?>
                        style="background-image:url('<?= h(base_url('download.php?photo=' . basename($person['photo_path']))) ?>'); background-size:cover;"
                    <?php endif; ?>
                ></div>
                <p class="person-card-name"><?= h($person['full_name']) ?></p>
                <p class="person-card-subtitle"><?= h($person['applicant_headline'] ?? $person['company_name'] ?? ucfirst($person['role'])) ?></p>
                <form method="post" action="<?= h(base_url('network/connect.php')) ?>">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="recipient_id" value="<?= (int) $person['id'] ?>">
                    <button type="submit" class="btn btn-primary">Connect</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

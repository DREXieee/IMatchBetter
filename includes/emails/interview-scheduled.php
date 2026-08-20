<?php
/** @var string $applicantName */
/** @var string $jobTitle */
/** @var string $companyName */
/** @var string $scheduledAt */
/** @var string $mode */
/** @var string|null $locationOrLink */
?>
<h2 style="margin:0 0 12px;">You're invited to interview!</h2>
<p>Hi <?= h($applicantName) ?>, <strong><?= h($companyName) ?></strong> would like to interview you for <strong><?= h($jobTitle) ?></strong>.</p>
<p style="font-size:16px; font-weight:700; color:#2f6fed;"><?= h($scheduledAt) ?> &middot; <?= h(ucfirst($mode)) ?></p>
<?php if (!empty($locationOrLink)): ?>
    <p><?= h($locationOrLink) ?></p>
<?php endif; ?>
<p>Please confirm or decline this interview from your dashboard.</p>
<?= email_button(base_url('applicant/interviews/index.php'), 'View Interview') ?>

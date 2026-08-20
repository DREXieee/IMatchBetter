<?php
/** @var string $applicantName */
/** @var string $jobTitle */
/** @var string $status */
$statusLabels = ['confirmed' => 'confirmed', 'declined' => 'declined'];
?>
<h2 style="margin:0 0 12px;">Interview response received</h2>
<p><strong><?= h($applicantName) ?></strong> has <?= h($statusLabels[$status] ?? $status) ?> the interview for <strong><?= h($jobTitle) ?></strong>.</p>
<?= email_button(base_url('employer/interviews/index.php'), 'View Interviews') ?>

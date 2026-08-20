<?php
/** @var string $applicantName */
/** @var string $jobTitle */
/** @var string $status */
$statusLabels = ['submitted' => 'Submitted', 'reviewed' => 'Reviewed', 'interview' => 'Interview', 'rejected' => 'Not Selected', 'hired' => 'Hired'];
?>
<h2 style="margin:0 0 12px;">Your application status changed</h2>
<p>Hi <?= h($applicantName) ?>, your application for <strong><?= h($jobTitle) ?></strong> is now:</p>
<p style="font-size:18px; font-weight:700; color:#2f6fed;"><?= h($statusLabels[$status] ?? $status) ?></p>
<?= email_button(base_url('applicant/my-applications.php'), 'View Application') ?>

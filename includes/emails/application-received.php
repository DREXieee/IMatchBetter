<?php
/** @var string $applicantName */
/** @var string $jobTitle */
?>
<h2 style="margin:0 0 12px;">We received your application!</h2>
<p>Hi <?= h($applicantName) ?>, your application for <strong><?= h($jobTitle) ?></strong> has been submitted successfully. The employer will review it and update your status.</p>
<?= email_button(base_url('applicant/my-applications.php'), 'Track My Applications') ?>

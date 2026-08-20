<?php
/** @var string $applicantName */
/** @var string $jobTitle */
/** @var int $applicationId */
?>
<h2 style="margin:0 0 12px;">New applicant!</h2>
<p><strong><?= h($applicantName) ?></strong> just applied to your job posting <strong><?= h($jobTitle) ?></strong>.</p>
<?= email_button(base_url('employer/applicants/view.php?id=' . $applicationId), 'Review Applicant') ?>

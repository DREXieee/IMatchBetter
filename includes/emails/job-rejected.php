<?php
/** @var string $jobTitle */
/** @var string $reason */
?>
<h2 style="margin:0 0 12px;">Update on your job posting</h2>
<p>An admin reviewed <strong><?= h($jobTitle) ?></strong> and was not able to approve it at this time.</p>
<p><strong>Reason:</strong> <?= h($reason) ?></p>
<p>You can edit the posting and set it to "Publish" again to resubmit it for review.</p>
<?= email_button(base_url('employer/jobs/index.php'), 'Edit Job Posting') ?>

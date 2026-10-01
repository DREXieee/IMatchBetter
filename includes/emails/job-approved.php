<?php
/** @var string $jobTitle */
/** @var string $jobUrl */
?>
<h2 style="margin:0 0 12px;">Your job posting is live!</h2>
<p>An admin has reviewed and approved <strong><?= h($jobTitle) ?></strong>. It's now visible to applicants.</p>
<?= email_button($jobUrl, 'View Job Posting') ?>

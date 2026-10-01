<?php
/** @var string $companyName */
/** @var string $reason */
?>
<h2 style="margin:0 0 12px;">Update on your IMatchBetter request</h2>
<p>An admin reviewed your employer registration for <strong><?= h($companyName) ?></strong> and was not able to approve it at this time.</p>
<p><strong>Reason:</strong> <?= h($reason) ?></p>
<p>You can update your company profile and resubmit for review at any time — there's no limit on resubmissions.</p>
<?= email_button(base_url('employer/pending-approval.php'), 'Update & Resubmit') ?>

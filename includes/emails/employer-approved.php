<?php /** @var string $companyName */ ?>
<h2 style="margin:0 0 12px;">You're approved, <?= h($companyName) ?>!</h2>
<p>Great news — an admin has reviewed and approved your employer account on IMatchBetter. You can now post job listings and start receiving applicants.</p>
<?= email_button(base_url('employer/jobs/create.php'), 'Post Your First Job') ?>

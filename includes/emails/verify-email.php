<?php
/** @var string $verifyUrl */
/** @var string $fullName */
?>
<h2 style="margin:0 0 12px;">Confirm your email address</h2>
<p>Hi <?= h($fullName) ?>, welcome to IMatchBetter! Please confirm this is your email address to unlock applying, reviews, and reporting. This link expires in 24 hours.</p>
<?= email_button($verifyUrl, 'Verify Email') ?>

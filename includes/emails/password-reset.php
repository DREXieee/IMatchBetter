<?php
/** @var string $resetUrl */
?>
<h2 style="margin:0 0 12px;">Reset your password</h2>
<p>We received a request to reset your IMatchBetter password. This link expires in 30 minutes. If you didn't request this, you can safely ignore this email.</p>
<?= email_button($resetUrl, 'Reset Password') ?>

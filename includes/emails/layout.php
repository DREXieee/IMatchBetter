<?php
/** @var string $templatePath */
$primary = '#2f6fed';
$textColor = '#1a1f36';
$muted = '#5b6270';
$border = '#e2e6ee';
?>
<!doctype html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0; padding:0; background:#f5f7fb; font-family: Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fb; padding: 32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid <?= $border ?>;">
    <tr>
        <td style="padding:24px 32px; border-bottom:1px solid <?= $border ?>;">
            <span style="font-size:20px; font-weight:800; color:<?= $textColor ?>;">IMatch<span style="color:<?= $primary ?>;">Better</span></span>
        </td>
    </tr>
    <tr>
        <td style="padding:32px; color:<?= $textColor ?>; font-size:15px; line-height:1.55;">
            <?php require $templatePath; ?>
        </td>
    </tr>
    <tr>
        <td style="padding:20px 32px; border-top:1px solid <?= $border ?>; color:<?= $muted ?>; font-size:12px;">
            &copy; <?= date('Y') ?> IMatchBetter. This is an automated message — please don't reply directly to this email.
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>

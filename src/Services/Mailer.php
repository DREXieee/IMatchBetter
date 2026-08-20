<?php

namespace IMatchBetter\Services;

use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

class Mailer
{
    /**
     * Renders an HTML email template and sends it. Never throws — a failure is logged and
     * swallowed so a flaky SMTP server can never block the core action (approval, application,
     * status change, etc.) that triggered the email.
     */
    public static function send(string $toEmail, string $toName, string $subject, string $template, array $data = []): bool
    {
        try {
            $body = self::renderTemplate($template, $data);
            $altBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $body));

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'] ?? '';
            $mail->Port = (int) ($_ENV['MAIL_PORT'] ?? 2525);
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'] ?? '';
            $mail->Password = $_ENV['MAIL_PASSWORD'] ?? '';
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';

            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@imatchbetter.local', $_ENV['MAIL_FROM_NAME'] ?? 'IMatchBetter');
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $body;
            $mail->AltBody = $altBody;

            $mail->send();

            return true;
        } catch (Throwable $e) {
            error_log('[Mailer] Failed to send "' . $subject . '" to ' . $toEmail . ': ' . $e->getMessage());

            return false;
        }
    }

    private static function renderTemplate(string $template, array $data): string
    {
        extract($data, EXTR_SKIP);
        $templatePath = BASE_PATH . '/includes/emails/' . $template . '.php';

        ob_start();
        require BASE_PATH . '/includes/emails/layout.php';

        return ob_get_clean();
    }
}

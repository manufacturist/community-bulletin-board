<?php

declare(strict_types=1);

namespace App\Core\Email;

use PHPMailer\PHPMailer\PHPMailer;

final class SmtpEmailAdapter implements EmailAdapter
{
    private PHPMailer $mailer;
    private bool $initialized = false;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
    }

    /**
     * @throws \Exception
     */
    #[\Override]
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        try {
            $this->mailer->isSMTP();
            $this->mailer->SMTPAuth = true;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

            $this->mailer->Host = isset($_ENV['EMAIL_SMTP_HOST']) && is_string($_ENV['EMAIL_SMTP_HOST'])
                ? $_ENV['EMAIL_SMTP_HOST']
                : throw new \RuntimeException("SMTP host is not set.");

            $this->mailer->Username = isset($_ENV['EMAIL_SMTP_USERNAME']) && is_string($_ENV['EMAIL_SMTP_USERNAME'])
                ? $_ENV['EMAIL_SMTP_USERNAME']
                : throw new \RuntimeException("SMTP user is not set.");

            $this->mailer->Password = isset($_ENV['EMAIL_SMTP_PASSWORD']) && is_string($_ENV['EMAIL_SMTP_PASSWORD'])
                ? $_ENV['EMAIL_SMTP_PASSWORD']
                : throw new \RuntimeException("SMTP password is not set.");

            $this->mailer->Port = isset($_ENV['EMAIL_SMTP_PORT']) && is_numeric($_ENV['EMAIL_SMTP_PORT'])
                ? (int)$_ENV['EMAIL_SMTP_PORT']
                : 587;

            $this->initialized = true;
        } catch (\Exception $e) {
            throw new \Exception(sprintf(gettext('email_error_smtp_config'), $e->getMessage()));
        }
    }

    /**
     * @throws \Exception
     */
    #[\Override]
    public function send(string $to, string $subject, string $htmlBody, string $textBody): void
    {
        $this->initialize();

        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->CharSet = PHPMailer::CHARSET_UTF8;

            $fromEmail = isset($_ENV['EMAIL_SMTP_USERNAME']) && is_string($_ENV['EMAIL_SMTP_USERNAME'])
                ? $_ENV['EMAIL_SMTP_USERNAME']
                : throw new \RuntimeException("SMTP user is not set.");

            $displayName = isset($_ENV['EMAIL_FROM_NAME']) && is_string($_ENV['EMAIL_FROM_NAME'])
                ? $_ENV['EMAIL_FROM_NAME']
                : gettext('Community Bulletin Board');

            $this->mailer->setFrom($fromEmail, $displayName);

            $this->mailer->isHTML();
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = $textBody;
            $this->mailer->send();
        } catch (\Exception $e) {
            throw new \Exception(sprintf(gettext('email_error_sending'), $this->mailer->ErrorInfo));
        }
    }
}

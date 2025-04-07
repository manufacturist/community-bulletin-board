<?php

declare(strict_types=1);

namespace App\Core\Email;

final class LoggingEmailAdapter implements EmailAdapter
{
    #[\Override]
    public function initialize(): void
    {
    }

    #[\Override]
    public function send(string $to, string $subject, string $htmlBody, string $textBody): void
    {
        // Use direct gettext function call to ensure translations work
        $fromInfo = isset($_ENV['EMAIL_FROM_NAME']) && is_string($_ENV['EMAIL_FROM_NAME'])
            ? $_ENV['EMAIL_FROM_NAME']
            : gettext('Community Bulletin Board');

        $message = sprintf(
            "%s\n%s\n%s%s\n\n%s",
            gettext('email_invitation_log_header'),
            sprintf(gettext('email_invitation_log_to'), $to),
            sprintf(gettext('email_invitation_log_subject'), $subject),
            $fromInfo,
            $textBody
        );

        error_log($message);
    }
}

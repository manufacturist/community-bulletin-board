<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Email;
use App\Core\Exceptions\Anomaly;
use App\Exceptions\EmailSendingException;
use App\Core\Types\Base64String;

final class EmailService
{
    /**
     * @throws EmailSendingException
     * @throws Anomaly
     */
    public static function sendInvitationEmail(string $email, Base64String $invitationToken, bool $isAdmin): void
    {
        $role = $isAdmin ? gettext('email_role_admin') : gettext('email_role_member');

        $appUrl = isset($_ENV['APP_URL']) && is_string($_ENV['APP_URL'])
            ? $_ENV['APP_URL']
            : throw new Anomaly('APP_URL environment variable is not set');

        $joinPath = _('url_join');
        $joinUrl = "$appUrl/$joinPath?t=" . urlencode($invitationToken->value);

        $subject = gettext('email_invitation_subject');

        $htmlBody = sprintf(
            "<h2>%s</h2>
            <p>%s</p>
            <p>%s</p>
            <p><a href='%s'>%s</a></p>
            <p>%s</p>",
            gettext('email_invitation_html_title'),
            sprintf(gettext('email_invitation_role_html'), $role),
            gettext('email_invitation_click_link_html'),
            $joinUrl,
            $joinUrl,
            gettext('email_invitation_expiry')
        );

        $textBody = sprintf(
            "%s\n\n%s\n%s\n\n%s",
            sprintf(gettext('email_invitation_role_text'), $role),
            gettext('email_invitation_click_link_text'),
            $joinUrl,
            gettext('email_invitation_expiry')
        );

        try {
            Email::send($email, $subject, $htmlBody, $textBody);
        } catch (\Exception $e) {
            throw new EmailSendingException('Failed to send invitation email: ' . $e->getMessage());
        }
    }
}

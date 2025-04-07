<?php

declare(strict_types=1);

namespace App\Core\Email;

interface EmailAdapter
{
    /**
     * Initialize the email adapter with necessary configuration
     *
     * @throws \Exception
     */
    public function initialize(): void;

    /**
     * Send an email
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML body content (for HTML-capable email clients)
     * @param string $textBody Plain text body content (for text-only email clients or logging)
     * @throws \Exception If the email could not be sent
     */
    public function send(string $to, string $subject, string $htmlBody, string $textBody): void;
}

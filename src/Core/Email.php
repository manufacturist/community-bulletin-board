<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Email\EmailAdapter;
use App\Core\Email\LoggingEmailAdapter;
use App\Core\Email\SmtpEmailAdapter;

final class Email
{
    private static ?self $instance = null;
    private EmailAdapter $adapter;

    /** @var array<string, EmailAdapter> Custom adapters for testing */
    private static array $customAdapters = [];

    /**
     * @throws \Exception
     */
    private function __construct()
    {
        $adapterType = isset($_ENV['EMAIL_ADAPTER']) && is_string($_ENV['EMAIL_ADAPTER'])
            ? $_ENV['EMAIL_ADAPTER']
            : 'logging';

        // Check for custom adapters first (used in testing)
        if (isset(self::$customAdapters[$adapterType])) {
            $this->adapter = self::$customAdapters[$adapterType];
        } else {
            $this->adapter = match ($adapterType) {
                'smtp' => new SmtpEmailAdapter(),
                'logging' => new LoggingEmailAdapter(),
                default => throw new \Exception("Invalid email adapter type: $adapterType")
            };
        }

        $this->adapter->initialize();
    }

    /**
     * @throws \Exception
     */
    private static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register a custom adapter for testing purposes
     *
     * @param string $name Name to identify the adapter
     * @param EmailAdapter $adapter The adapter instance
     */
    public static function registerAdapter(string $name, EmailAdapter $adapter): void
    {
        self::$customAdapters[$name] = $adapter;
        // Reset instance to force recreation with the new adapter
        self::$instance = null;
    }

    /**
     * Clear all custom adapters (useful for query-migration teardown)
     */
    public static function clearCustomAdapters(): void
    {
        self::$customAdapters = [];
        self::$instance = null;
    }

    /**
     * Send an email using the configured adapter
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML body content (for HTML-capable email clients)
     * @param string $textBody Plain text body content (for text-only email clients or logging)
     * @throws \Exception If the email could not be sent
     */
    public static function send(string $to, string $subject, string $htmlBody, string $textBody): void
    {
        self::getInstance()->adapter->send($to, $subject, $htmlBody, $textBody);
    }
}

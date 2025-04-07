<?php

declare(strict_types=1);

namespace App\Tests\Suites\Services;

use App\Core\Email;
use App\Core\Email\EmailAdapter;
use App\Exceptions\EmailSendingException;
use App\Core\Types\Base64String;
use App\Core\Types\Binary;
use App\Services\EmailService;
use App\Tests\TestKit\TestCases\BaseTestCase;

class EmailServiceTest extends BaseTestCase
{
    private static string $testEmail = 'query-migration@example.com';
    private static Binary $testToken;
    private static Base64String $testBase64Token;
    private TestEmailAdapter $testAdapter;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$testToken = Binary::apply(random_bytes(16));
        self::$testBase64Token = Base64String::fromBytes(self::$testToken);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->testAdapter = new TestEmailAdapter();
        $_ENV['EMAIL_ADAPTER'] = 'query-migration';
        $_ENV['APP_URL'] = 'http://test.example.com';

        Email::registerAdapter('query-migration', $this->testAdapter);
    }

    public static function tearDownAfterClass(): void
    {
        $_ENV['EMAIL_ADAPTER'] = 'logging';
        Email::clearCustomAdapters();
    }

    public function testSendInvitationEmailWithTranslations(): void
    {
        // Act
        EmailService::sendInvitationEmail(self::$testEmail, self::$testBase64Token, false);

        // Assert
        $lastEmail = $this->testAdapter->getLastEmail();
        $this->assertNotNull($lastEmail, 'No email was sent');

        $this->assertNotEquals('email_invitation_subject', $lastEmail['subject'], 'Translation key was not replaced');
        $this->assertStringNotContainsString('email_invitation_html_title', $lastEmail['htmlBody'], 'HTML contains untranslated keys');
        $this->assertStringNotContainsString('email_invitation_role_text', $lastEmail['textBody'], 'Text contains untranslated keys');

        $this->assertStringContainsString('join?t=', $lastEmail['htmlBody'], 'HTML missing token URL');
        $this->assertStringContainsString('join?t=', $lastEmail['textBody'], 'Text missing token URL');
        $this->assertStringContainsString('member', strtolower($lastEmail['textBody']), 'Email should mention member role');
    }

    public function testSendInvitationEmailWithAdminRole(): void
    {
        // Act
        EmailService::sendInvitationEmail(self::$testEmail, self::$testBase64Token, true);

        // Assert
        $lastEmail = $this->testAdapter->getLastEmail();
        $this->assertNotNull($lastEmail, 'No email was sent');

        $this->assertStringContainsString('admin', strtolower($lastEmail['htmlBody']), 'HTML should mention admin role');
        $this->assertStringContainsString('admin', strtolower($lastEmail['textBody']), 'Text should mention admin role');
    }

    public function testSendInvitationEmailWithEmailError(): void
    {
        // Arrange
        $this->testAdapter->setShouldThrowException(true);

        // Act & Assert
        $this->expectException(EmailSendingException::class);
        EmailService::sendInvitationEmail(self::$testEmail, self::$testBase64Token, false);
    }
}

/**
 * Test adapter for capturing emails during tests
 */
class TestEmailAdapter implements EmailAdapter
{
    private array $lastEmail = [];
    private bool $shouldThrowException = false;

    public function initialize(): void
    {
    }

    public function send(string $to, string $subject, string $htmlBody, string $textBody): void
    {
        if ($this->shouldThrowException) {
            throw new \Exception('Test email exception');
        }

        $this->lastEmail = [
            'to' => $to,
            'subject' => $subject,
            'htmlBody' => $htmlBody,
            'textBody' => $textBody
        ];
    }

    public function getLastEmail(): array
    {
        return $this->lastEmail;
    }

    public function setShouldThrowException(bool $shouldThrow): void
    {
        $this->shouldThrowException = $shouldThrow;
    }
}

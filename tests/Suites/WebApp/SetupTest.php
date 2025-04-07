<?php

declare(strict_types=1);

namespace App\Tests\Suites\WebApp;

use App\Controllers\RequestDTOs\AcceptInvitationDTO;
use App\Core\MariaTransactor;
use App\Core\Types\Base64String;
use App\Domain\Repositories\InvitationRepo;
use App\Tests\TestKit\TestCases\WebAppTestCase;
use App\Tests\TestKit\WebApp\WebAppAPI;

final class SetupTest extends WebAppTestCase
{
    private static ?string $baseUrl = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$baseUrl = "http://127.0.0.1:" . self::$webAppPort;

        MariaTransactor::update("SET FOREIGN_KEY_CHECKS = 0");
        MariaTransactor::update("DROP TABLE setup");
        MariaTransactor::update("DROP TABLE authentications");
        MariaTransactor::update("DROP TABLE posts");
        MariaTransactor::update("DROP TABLE users");
        MariaTransactor::update("DROP TABLE invitations");
        MariaTransactor::update("SET FOREIGN_KEY_CHECKS = 1");
    }

    public function testSetupEndpoint(): void
    {
        // Arrange
        $api = new WebAppAPI(self::$baseUrl);

        // Act
        $response = $api->setup();

        $invitation = InvitationRepo::selectAll()[0];
        $token = Base64String::fromBytes($invitation->token)->value;
        $expectedLocationUrl = "http://localhost:8000/join?t=" . urlencode($token);

        $acceptInvitation = new AcceptInvitationDTO(
            token: $token,
            name: self::$faker->firstName(),
            phoneNumber: self::$faker->phoneNumber(),
            password: self::$faker->password(8)
        );

        $acceptInvitationResponse = $api->acceptInvitation($acceptInvitation);

        // Assert
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertArrayHasKey('Location', $response->getHeaders());
        $this->assertEquals($expectedLocationUrl, $response->getHeaderLine('Location'));

        self::assertEquals(204, $acceptInvitationResponse->getStatusCode());
    }
}

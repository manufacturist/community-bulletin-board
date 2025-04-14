<?php

declare(strict_types=1);

namespace App\Tests\Suites\WebApp;

use App\Controllers\RequestDTOs\AcceptInvitationDTO;
use App\Core\MariaTransactor;
use App\Core\Types\Base64String;
use App\Domain\Repositories\InvitationRepo;
use App\Domain\Repositories\MigrationRepo;
use App\Services\InstallService;
use App\Tests\TestKit\TestCases\WebAppTestCase;
use App\Tests\TestKit\WebApp\WebAppAPI;

final class SetupTest extends WebAppTestCase
{
    private static ?string $baseUrl = null;

    protected function setUp(): void
    {
        parent::setUp();

        MariaTransactor::update("SET FOREIGN_KEY_CHECKS = 0");
        MariaTransactor::update("DROP TABLE authentications");
        MariaTransactor::update("DROP TABLE posts");
        MariaTransactor::update("DROP TABLE users");
        MariaTransactor::update("DROP TABLE invitations");
        MariaTransactor::update("DROP TABLE migrations");
        MariaTransactor::update("SET FOREIGN_KEY_CHECKS = 1");
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$baseUrl = "http://127.0.0.1:" . self::$webAppPort;
    }

    public function testInstallEndpoint(): void
    {
        // Arrange
        $api = new WebAppAPI(self::$baseUrl);

        // Act
        $response = $api->install();

        // Assert
        $this->assertEquals(302, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertArrayHasKey('Location', $response->getHeaders());

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

        $this->assertEquals($expectedLocationUrl, $response->getHeaderLine('Location'));
        $this->assertEquals(204, $acceptInvitationResponse->getStatusCode());
    }

    public function testUpdateEndpoint(): void
    {
        // Arrange
        $api = new WebAppAPI(self::$baseUrl);
        $api->install();
        $api->createAuthenticatedUser(isAdmin: true);

        $migrationDir = dirname(__DIR__, 3) . '/database/query-migration';
        $numberOfMigrations = count(scandir($migrationDir)) - 2 + 1; // -2 for . and ..
        $newMigrationFile = "$migrationDir/v$numberOfMigrations" . "_new_migration.sql";

        file_put_contents($newMigrationFile, 'CREATE TABLE IF NOT EXISTS new_table (id INT PRIMARY KEY);');

        // Act
        try {
            // This does nothing because the new migration is not in the container
            $response = $api->update();

            // Run the update endpoint business logic
            InstallService::update();

            $latestMigrationId = MigrationRepo::getLatestMigrationId();

            // Assert
            $this->assertEquals(302, $response->getStatusCode(), $response->getBody()->getContents());
            $this->assertEquals($numberOfMigrations, $latestMigrationId);
        } finally {
            unlink($newMigrationFile);
        }
    }
}

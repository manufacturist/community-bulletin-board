<?php

declare(strict_types=1);

namespace App\Tests\Suites\WebApp;

use App\Core\MariaTransactor;
use App\Tests\TestKit\WebApp\WebAppAPI;
use App\Tests\TestKit\TestCases\WebAppTestCase;

final class AdminUpdateRoleTest extends WebAppTestCase
{
    private static ?string $baseUrl = null;

    protected function setUp(): void
    {
        MariaTransactor::update("SET FOREIGN_KEY_CHECKS = 0");
        MariaTransactor::update("TRUNCATE TABLE authentications");
        MariaTransactor::update("TRUNCATE TABLE posts");
        MariaTransactor::update("TRUNCATE TABLE users");
        MariaTransactor::update("TRUNCATE TABLE invitations");
        MariaTransactor::update("SET FOREIGN_KEY_CHECKS = 1");
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$baseUrl = "http://127.0.0.1:" . self::$webAppPort;
    }

    public function testOwnerCanUpdateRole(): void
    {
        // Arrange
        $apiAdmin = new WebAppAPI(self::$baseUrl);
        $apiAdmin->createAuthenticatedUser(isAdmin: true);

        $apiUser = new WebAppAPI(self::$baseUrl);
        $userInfo = $apiUser->createAuthenticatedUser(isAdmin: false);

        // Act
        $userInfo1 = $apiAdmin->updateUserRoleChecked($userInfo->id, 'admin');
        $userInfo2 = $apiAdmin->updateUserRoleChecked($userInfo->id, 'member');

        // Assert
        $this->assertEquals('admin', $userInfo1->role);
        $this->assertEquals('member', $userInfo2->role);
    }

    public function testAdminCantUpdateRole(): void
    {
        // Arrange
        $apiOwner = new WebAppAPI(self::$baseUrl);
        $apiOwner->createAuthenticatedUser(isAdmin: true);

        $apiAdmin = new WebAppAPI(self::$baseUrl);
        $apiAdmin->createAuthenticatedUser(isAdmin: true);

        $apiUser = new WebAppAPI(self::$baseUrl);
        $userInfo = $apiUser->createAuthenticatedUser(isAdmin: false);

        // Act
        $response1 = $apiAdmin->updateUserRole($userInfo->id, 'admin');
        $response2 = $apiUser->updateUserRole($userInfo->id, 'member');

        $body1 = json_decode($response1->getBody()->getContents(), true);
        $body2 = json_decode($response2->getBody()->getContents(), true);

        // Assert
        $this->assertEquals(403, $response1->getStatusCode());
        $this->assertEquals(403, $response2->getStatusCode());

        $this->assertEquals('Only owner can update user roles.', $body1['error']);
        $this->assertEquals($body1, $body2);
    }

    public function testOwnerCantUpdateOwnRole(): void
    {
        // Arrange
        $api = new WebAppAPI(self::$baseUrl);
        $userInfo = $api->createAuthenticatedUser(isAdmin: true);

        // Act
        $response = $api->updateUserRole($userInfo->id, 'admin');

        // Assert
        $this->assertEquals(403, $response->getStatusCode());
    }
}

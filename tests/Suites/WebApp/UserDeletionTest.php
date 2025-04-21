<?php

declare(strict_types=1);

namespace App\Tests\Suites\WebApp;

use App\Core\MariaTransactor;
use App\Tests\TestKit\WebApp\WebAppAPI;
use App\Tests\TestKit\TestCases\WebAppTestCase;

final class UserDeletionTest extends WebAppTestCase
{
    private static ?string $baseUrl = null;
    private static ?WebAppAPI $ownerApi = null;
    private static ?object $ownerInfo = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$baseUrl = "http://127.0.0.1:" . self::$webAppPort;

        MariaTransactor::update("SET FOREIGN_KEY_CHECKS = 0");
        MariaTransactor::update("TRUNCATE TABLE authentications");
        MariaTransactor::update("TRUNCATE TABLE users");
        MariaTransactor::update("SET FOREIGN_KEY_CHECKS = 1");

        self::$ownerApi = new WebAppAPI(self::$baseUrl);
        self::$ownerInfo = self::$ownerApi->createAuthenticatedUser(isAdmin: true);
    }

    public function testRegularUserCanDeleteSelf(): void
    {
        // Arrange
        $apiUser = new WebAppAPI(self::$baseUrl);
        $apiUserInfo = $apiUser->createAuthenticatedUser(isAdmin: false);

        // Act
        $response = $apiUser->deleteUser($apiUserInfo->id);

        // Assert
        self::assertEquals(204, $response->getStatusCode(), $response->getBody()->getContents());
    }

    public function testRegularUserCannotDeleteOtherUser(): void
    {
        // Arrange
        $apiUser1 = new WebAppAPI(self::$baseUrl);
        $apiUser1->createAuthenticatedUser(isAdmin: false);

        $apiUser2 = new WebAppAPI(self::$baseUrl);
        $apiUser2Info = $apiUser2->createAuthenticatedUser(isAdmin: false);

        // Act
        $response = $apiUser1->deleteUser($apiUser2Info->id);

        // Assert
        self::assertEquals(403, $response->getStatusCode(), $response->getBody()->getContents());
    }

    public function testRegularUserCannotDeleteAdmin(): void
    {
        // Arrange
        $apiUser = new WebAppAPI(self::$baseUrl);
        $apiUser->createAuthenticatedUser(isAdmin: false);

        $apiAdmin = new WebAppAPI(self::$baseUrl);
        $apiAdminInfo = $apiAdmin->createAuthenticatedUser(isAdmin: true);

        // Act
        $response = $apiUser->deleteUser($apiAdminInfo->id);

        // Assert
        self::assertEquals(403, $response->getStatusCode(), $response->getBody()->getContents());
    }

    public function testAdminCanDeleteRegularUser(): void
    {
        // Arrange
        $apiAdmin = new WebAppAPI(self::$baseUrl);
        $apiAdmin->createAuthenticatedUser(isAdmin: true);

        $apiUser = new WebAppAPI(self::$baseUrl);
        $userInfo = $apiUser->createAuthenticatedUser(isAdmin: false);

        // Act
        $response = $apiAdmin->deleteUser($userInfo->id);

        // Assert
        self::assertEquals(204, $response->getStatusCode(), $response->getBody()->getContents());
    }

    public function testAdminCannotDeleteOtherAdmin(): void
    {
        // Arrange
        $apiAdmin1 = new WebAppAPI(self::$baseUrl);
        $apiAdmin1->createAuthenticatedUser(isAdmin: true);

        $apiAdmin2 = new WebAppAPI(self::$baseUrl);
        $adminInfo2 = $apiAdmin2->createAuthenticatedUser(isAdmin: true);

        // Act
        $response = $apiAdmin1->deleteUser($adminInfo2->id);

        // Assert
        self::assertEquals(403, $response->getStatusCode(), $response->getBody()->getContents());
    }

    public function testAdminCannotDeleteSelf(): void
    {
        // Arrange
        $apiAdmin = new WebAppAPI(self::$baseUrl);
        $apiAdminInfo = $apiAdmin->createAuthenticatedUser(isAdmin: true);

        // Act
        $response = $apiAdmin->deleteUser($apiAdminInfo->id);

        // Assert
        self::assertEquals(403, $response->getStatusCode(), $response->getBody()->getContents());
    }

    public function testOwnerCannotDeleteSelf(): void
    {
        // Act
        $response = self::$ownerApi->deleteUser(self::$ownerInfo->id);

        // Assert
        self::assertEquals(403, $response->getStatusCode(), $response->getBody()->getContents());
    }

    public function testOwnerCanDeleteAdmin(): void
    {
        // Arrange
        $apiAdmin = new WebAppAPI(self::$baseUrl);
        $adminInfo = $apiAdmin->createAuthenticatedUser(isAdmin: true);

        // Act
        $response = self::$ownerApi->deleteUser($adminInfo->id);

        // Assert
        self::assertEquals(204, $response->getStatusCode(), $response->getBody()->getContents());
    }
}

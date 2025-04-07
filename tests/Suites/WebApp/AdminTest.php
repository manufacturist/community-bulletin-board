<?php

declare(strict_types=1);

namespace App\Tests\Suites\WebApp;

use App\Controllers\RequestDTOs\NewInvitationDTO;
use App\Core\MariaTransactor;
use App\Tests\TestKit\WebApp\WebAppAPI;
use App\Tests\TestKit\WebApp\WebAppPages;
use App\Tests\TestKit\TestCases\WebAppTestCase;

final class AdminTest extends WebAppTestCase
{
    private static ?string $baseUrl = null;

    protected function tearDown(): void
    {
        parent::tearDown();

        MariaTransactor::update("TRUNCATE TABLE posts");
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$baseUrl = "http://127.0.0.1:" . self::$webAppPort;
    }

    public function testOnlyAdminCanDeletePosts(): void
    {
        // Arrange
        $apiAdmin = new WebAppAPI(self::$baseUrl);
        $apiAdmin->createAuthenticatedUser(isAdmin: true);

        $apiUser = new WebAppAPI(self::$baseUrl);
        $apiUser->createAuthenticatedUser(isAdmin: false);

        $adminPost = $apiAdmin->createPostChecked();
        $userPost = $apiUser->createPostChecked();

        // Act
        $apiAdmin->deletePostChecked($userPost->id);
        $response = $apiUser->deletePost($adminPost->id);

        // Assert
        self::assertEquals(403, $response->getStatusCode());
    }

    public function testOnlyAdminCanSendInvitation(): void
    {
        // Arrange
        $apiAdmin = new WebAppAPI(self::$baseUrl);
        $apiAdmin->createAuthenticatedUser(isAdmin: true);

        $apiUser = new WebAppAPI(self::$baseUrl);
        $apiUser->createAuthenticatedUser(isAdmin: false);

        // Act
        $invitation = new NewInvitationDTO(self::$faker->email(), isAdmin: false);
        $userInviteResponse = $apiUser->invite($invitation);
        $adminInviteResponse = $apiAdmin->invite($invitation);

        // Assert
        self::assertEquals(403, $userInviteResponse->getStatusCode());
        self::assertEquals(204, $adminInviteResponse->getStatusCode());
    }

    public function testAdminCanViewMembersList(): void
    {
        // Arrange
        $apiAdmin = new WebAppAPI(self::$baseUrl);
        $adminPages = new WebAppPages(self::$baseUrl, $apiAdmin->client);
        $apiAdminInfo = $apiAdmin->createAuthenticatedUser(isAdmin: true);

        $apiUser = new WebAppAPI(self::$baseUrl);
        $apiUserInfo = $apiUser->createAuthenticatedUser(isAdmin: false);

        // Act
        $adminPage = $adminPages->getAdminPage();

        // Assert
        $html = $adminPage->getBody()->getContents();

        self::assertEquals(200, $adminPage->getStatusCode());
        self::assertStringContainsString("data-member-id=\"$apiAdminInfo->id\"", $html);
        self::assertStringContainsString($apiAdminInfo->name, $html);
        self::assertStringContainsString($apiAdminInfo->email, $html);

        self::assertStringContainsString("data-member-id=\"$apiUserInfo->id\"", $html);
        self::assertStringContainsString($apiAdminInfo->name, $html);
        self::assertStringContainsString($apiAdminInfo->email, $html);
    }

    public function testAdminCanDeleteUser(): void
    {
        // Arrange
        $apiAdmin = new WebAppAPI(self::$baseUrl);
        $apiAdmin->createAuthenticatedUser(isAdmin: true);

        $apiUser = new WebAppAPI(self::$baseUrl);
        $apiUserInfo = $apiUser->createAuthenticatedUser(isAdmin: false);

        $pages = new WebAppPages(self::$baseUrl, $apiAdmin->client);

        // Act
        try {
            $response = $apiAdmin->deleteUser($apiUserInfo->id);
            $responseBody = $response->getBody()->getContents();
            $statusCode = $response->getStatusCode();

            // Assert
            self::assertEquals(204, $statusCode, "Delete user response should be 204, got: $statusCode, body: $responseBody");
        } catch (\Exception $e) {
            self::fail("Exception when deleting user: " . $e->getMessage());
        }

        $adminPageAfterDelete = $pages->getAdminPage();
        $htmlAfterDelete = $adminPageAfterDelete->getBody()->getContents();
        self::assertStringNotContainsString($apiUserInfo->email, $htmlAfterDelete);
        self::assertStringNotContainsString($apiUserInfo->name, $htmlAfterDelete);
    }
}

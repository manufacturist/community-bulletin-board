<?php

declare(strict_types=1);

namespace App\Tests\Suites\WebApp;

use App\Domain\Repositories\UserRepo;
use App\Tests\TestKit\WebApp\WebAppAPI;
use App\Tests\TestKit\TestCases\WebAppTestCase;

final class AdminUpdateMaxPostsTest extends WebAppTestCase
{
    private static ?string $baseUrl = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$baseUrl = "http://127.0.0.1:" . self::$webAppPort;
    }

    public function testAdminCanIncreaseUserMaxPosts(): void
    {
        // Arrange
        $apiAdmin = new WebAppAPI(self::$baseUrl);
        $apiAdmin->createAuthenticatedUser(isAdmin: true);

        $apiUser = new WebAppAPI(self::$baseUrl);
        $apiUserInfo = $apiUser->createAuthenticatedUser(isAdmin: false);

        $initialMaxPosts = $apiUserInfo->maxActivePosts;

        // Act
        $response = $apiAdmin->updateUserMaxPosts($apiUserInfo->id, 1);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals($initialMaxPosts + 1, $responseData['maxActivePosts']);

        $updatedUser = UserRepo::selectUserById($apiUserInfo->id);
        $this->assertEquals($initialMaxPosts + 1, $updatedUser->maxActivePosts);
    }

    public function testAdminCanDecreaseUserMaxPosts(): void
    {
        // Arrange
        $apiAdmin = new WebAppAPI(self::$baseUrl);
        $apiAdmin->createAuthenticatedUser(isAdmin: true);

        $apiUser = new WebAppAPI(self::$baseUrl);
        $apiUserInfo = $apiUser->createAuthenticatedUser(isAdmin: false);

        $apiAdmin->updateUserMaxPosts($apiUserInfo->id, 1);

        $userAfterIncrease = UserRepo::selectUserById($apiUserInfo->id);
        $increasedMaxPosts = $userAfterIncrease->maxActivePosts;

        // Act
        $updatedUserInfo = $apiAdmin->updateUserMaxPostsChecked($apiUserInfo->id, -1);

        // Assert
        $this->assertEquals($increasedMaxPosts - 1, $updatedUserInfo->maxActivePosts);
    }

    public function testNonAdminCannotUpdateMaxPosts(): void
    {
        // Arrange
        $api = new WebAppAPI(self::$baseUrl);
        $api->createAuthenticatedUser(isAdmin: true);
        $userInfo = $api->createAuthenticatedUser(isAdmin: false);

        // Act
        $response = $api->updateUserMaxPosts($userInfo->id, 1);

        // Assert
        $this->assertEquals(403, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Only administrators can update user post limits.', $body['error']);
    }

    public function testMaxPostsCannotExceedLimit(): void
    {
        // Arrange
        $apiAdmin = new WebAppAPI(self::$baseUrl);
        $apiAdmin->createAuthenticatedUser(isAdmin: true);

        $apiUser = new WebAppAPI(self::$baseUrl);
        $apiUserInfo = $apiUser->createAuthenticatedUser(isAdmin: false);

        for ($i = $apiUserInfo->maxActivePosts; $i < 5; $i++) {
            $apiAdmin->updateUserMaxPosts($apiUserInfo->id, 1);
        }

        $userAtMax = UserRepo::selectUserById($apiUserInfo->id);
        $this->assertEquals(5, $userAtMax->maxActivePosts);

        // Act
        $updatedUserInfo = $apiAdmin->updateUserMaxPostsChecked($apiUserInfo->id, 1);

        // Assert
        $this->assertEquals(5, $updatedUserInfo->maxActivePosts);
    }

    public function testMaxPostsCannotGoBelowZero(): void
    {
        // Arrange
        $apiAdmin = new WebAppAPI(self::$baseUrl);
        $apiAdmin->createAuthenticatedUser(isAdmin: true);

        $apiUser = new WebAppAPI(self::$baseUrl);
        $apiUserInfo = $apiUser->createAuthenticatedUser(isAdmin: false);

        for ($i = $apiUserInfo->maxActivePosts; $i > 0; $i--) {
            $apiAdmin->updateUserMaxPosts($apiUserInfo->id, -1);
        }

        $userAtMin = UserRepo::selectUserById($apiUserInfo->id);
        $this->assertEquals(0, $userAtMin->maxActivePosts);

        // Act
        $updatedUserInfo = $apiAdmin->updateUserMaxPostsChecked($apiUserInfo->id, -1);

        // Assert
        $this->assertEquals(0, $updatedUserInfo->maxActivePosts);
    }
}

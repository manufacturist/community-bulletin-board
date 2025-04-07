<?php

declare(strict_types=1);

namespace App\Tests\Suites\Services;

use App\Core\Exceptions\Forbidden;
use App\Core\MariaTransactor;
use App\Domain\Models\UserInfo;
use App\Domain\Repositories\UserRepo;
use App\Exceptions\UserNotFound;
use App\Services\UserService;
use App\Tests\TestKit\TestCases\BaseTestCase;

final class UserServiceTest extends BaseTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        MariaTransactor::update("SET FOREIGN_KEY_CHECKS = 0");
        MariaTransactor::update("TRUNCATE TABLE authentications");
        MariaTransactor::update("TRUNCATE TABLE users");
        MariaTransactor::update("SET FOREIGN_KEY_CHECKS = 1");
    }

    public function testUpdateUserMaxActivePosts(): void
    {
        // Arrange
        $admin = self::addAuthenticatedAdmin()[0];
        $user = self::addAuthenticatedUser()[0];
        $initialMaxPosts = $user->maxActivePosts;

        // Act
        $updatedUser = UserService::updateUserMaxActivePosts($user->id, 1, $admin);

        // Assert
        $this->assertEquals($initialMaxPosts + 1, $updatedUser->maxActivePosts);

        // Act
        $updatedUser = UserService::updateUserMaxActivePosts($user->id, -1, $admin);

        // Assert
        $this->assertEquals($initialMaxPosts, $updatedUser->maxActivePosts);
    }

    public function testUpdateUserMaxActivePostsEnforcesLimits(): void
    {
        // Arrange
        $admin = self::addAuthenticatedAdmin()[0];

        $user = self::addCustomAuthenticatedUser(
            name: self::$faker->firstName(),
            email: self::$faker->email(),
            phoneNumber: self::$faker->phoneNumber(),
            maxActivePosts: 5
        )[0];

        // Act
        $updatedUser = UserService::updateUserMaxActivePosts($user->id, 1, $admin);

        // Assert
        $this->assertEquals(5, $updatedUser->maxActivePosts);

        // Act
        $updatedUser = UserService::updateUserMaxActivePosts($user->id, -5, $admin);

        // Assert
        $this->assertEquals(0, $updatedUser->maxActivePosts);

        // Act
        $updatedUser = UserService::updateUserMaxActivePosts($user->id, -1, $admin);

        // Assert
        $this->assertEquals(0, $updatedUser->maxActivePosts);
    }

    public function testNonAdminCannotUpdateMaxActivePosts(): void
    {
        // Arrange
        $user1 = self::addAuthenticatedUser()[0];
        $user2 = self::addAuthenticatedUser()[0];

        // Act & Assert
        $this->expectException(Forbidden::class);
        $this->expectExceptionMessage('Only administrators can update user post limits.');

        UserService::updateUserMaxActivePosts($user2->id, 1, $user1);
    }

    public function testFetchAll(): void
    {
        // Arrange
        $user1 = self::addAuthenticatedUser()[0];
        $user2 = self::addAuthenticatedUser()[0];
        $admin = self::addAuthenticatedAdmin()[0];

        // Act
        $users = UserService::fetchAll();

        // Assert
        $this->assertIsArray($users);
        $this->assertGreaterThanOrEqual(3, count($users));
        $this->assertContainsOnlyInstancesOf(UserInfo::class, $users);

        $userIds = array_map(fn($user) => $user->id, $users);
        $this->assertContains($user1->id, $userIds);
        $this->assertContains($user2->id, $userIds);
        $this->assertContains($admin->id, $userIds);
    }

    public function testOwnerCanDeleteRegularUser(): void
    {
        // Arrange
        $owner = self::addCustomAuthenticatedUser(
            name: self::$faker->firstName(),
            email: self::$faker->email(),
            phoneNumber: self::$faker->phoneNumber(),
            role: 'owner'
        )[0];

        $user = self::addAuthenticatedUser()[0];

        // Act
        UserService::deleteUser($user->id, $owner);

        // Assert
        $this->assertNull(UserRepo::selectUserById($user->id));
    }

    public function testOwnerCanDeleteAdmin(): void
    {
        // Arrange
        $owner = self::addCustomAuthenticatedUser(
            name: self::$faker->firstName(),
            email: self::$faker->email(),
            phoneNumber: self::$faker->phoneNumber(),
            role: 'owner'
        )[0];

        $admin = self::addAuthenticatedAdmin()[0];

        // Act
        UserService::deleteUser($admin->id, $owner);

        // Assert
        $this->assertNull(UserRepo::selectUserById($admin->id));
    }

    public function testOwnerCannotDeleteSelf(): void
    {
        // Arrange
        $owner = self::addCustomAuthenticatedUser(
            name: self::$faker->firstName(),
            email: self::$faker->email(),
            phoneNumber: self::$faker->phoneNumber(),
            role: 'owner'
        )[0];

        // Act & Assert
        $this->expectException(Forbidden::class);
        $this->expectExceptionMessage('You do not have permission to delete this user.');

        UserService::deleteUser($owner->id, $owner);
    }

    public function testAdminCanDeleteRegularUser(): void
    {
        // Arrange
        $admin = self::addAuthenticatedAdmin()[0];
        $user = self::addAuthenticatedUser()[0];

        // Act
        UserService::deleteUser($user->id, $admin);

        // Assert
        $this->assertNull(UserRepo::selectUserById($user->id));
    }

    public function testAdminCannotDeleteAnotherAdmin(): void
    {
        // Arrange
        $admin1 = self::addAuthenticatedAdmin()[0];
        $admin2 = self::addAuthenticatedAdmin()[0];

        // Act & Assert
        $this->expectException(Forbidden::class);
        $this->expectExceptionMessage('You do not have permission to delete this user.');

        UserService::deleteUser($admin2->id, $admin1);
    }

    public function testAdminCannotDeleteSelf(): void
    {
        // Arrange
        $admin = self::addAuthenticatedAdmin()[0];

        // Act & Assert
        $this->expectException(Forbidden::class);
        $this->expectExceptionMessage('You do not have permission to delete this user.');

        UserService::deleteUser($admin->id, $admin);
    }

    public function testRegularUserCannotDeleteOtherUser(): void
    {
        // Arrange
        $user1 = self::addAuthenticatedUser()[0];
        $user2 = self::addAuthenticatedUser()[0];

        // Act & Assert
        $this->expectException(Forbidden::class);
        $this->expectExceptionMessage('You do not have permission to delete this user.');

        UserService::deleteUser($user2->id, $user1);
    }

    public function testRegularUserCannotDeleteSelf(): void
    {
        // Arrange
        $user = self::addAuthenticatedUser()[0];

        // Act & Assert
        $this->expectException(Forbidden::class);
        $this->expectExceptionMessage('You do not have permission to delete this user.');

        UserService::deleteUser($user->id, $user);
    }

    public function testDeleteNonExistentUser(): void
    {
        // Arrange
        $admin = self::addAuthenticatedAdmin()[0];
        $nonExistentUserId = 9999;

        // Act & Assert
        $this->expectException(UserNotFound::class);
        $this->expectExceptionMessage("Failed to find user {$nonExistentUserId}.");

        UserService::deleteUser($nonExistentUserId, $admin);
    }

    public function testOnlyOneOwnerAllowedOnInsert(): void
    {
        // Arrange
        self::addCustomAuthenticatedUser(
            name: self::$faker->firstName(),
            email: self::$faker->email(),
            phoneNumber: self::$faker->phoneNumber(),
            role: 'owner'
        )[0];

        // Act & Assert
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage('SQLSTATE[45000]: <<Unknown error>>: 1644 Only one owner allowed');

        self::addCustomAuthenticatedUser(
            name: self::$faker->firstName(),
            email: self::$faker->email(),
            phoneNumber: self::$faker->phoneNumber(),
            role: 'owner'
        )[0];
    }

    public function testOnlyOneOwnerAllowedOnUpdate(): void
    {
        // Arrange
        self::addCustomAuthenticatedUser(
            name: self::$faker->firstName(),
            email: self::$faker->email(),
            phoneNumber: self::$faker->phoneNumber(),
            role: 'owner'
        )[0];

        $userInfo = self::addAuthenticatedUser()[0];

        // Act & Assert
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage('SQLSTATE[45000]: <<Unknown error>>: 1644 Only one owner allowed');

        $query = "UPDATE users SET role = 'owner' WHERE id = :id";
        MariaTransactor::update($query, [':id' => $userInfo->id]);
    }
}

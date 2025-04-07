<?php

declare(strict_types=1);

namespace App\Tests\Suites\Services;

use App\Core\MariaTransactor;
use App\Domain\Models\PostInfo;
use App\Services\PostService;
use App\Tests\TestKit\TestCases\BaseTestCase;

final class PostServiceTest extends BaseTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        MariaTransactor::update("TRUNCATE TABLE posts");
    }

    public function testCreatePost(): void
    {
        // Arrange
        $name = self::$faker->firstName();
        $phoneNumber = self::$faker->phoneNumber();
        $email = self::$faker->email();

        $user = self::addCustomAuthenticatedUser($name, $email, $phoneNumber)[0];

        // Act
        $description = self::$faker->text();
        $link = self::$faker->url();
        $pinColor = self::$faker->pinColor();
        $expiresAt = self::$faker->nearingDate();

        $post = PostService::createPost($user, $description, $pinColor, $link, $expiresAt);

        // Assert
        $this->assertEquals($name, $post->userName);
        $this->assertEquals($description, $post->description);
        $this->assertEquals($phoneNumber, $post->phoneNumber);
        $this->assertEquals($link, $post->link);
        $this->assertEquals($pinColor, $post->pinColor);
        $this->assertEquals($expiresAt, $post->expiresAt);
    }

    public function testGetAllPosts(): void
    {
        // Arrange
        $user = self::addAuthenticatedUser()[0];

        // Act
        $post = self::createPost($user);
        $posts = PostService::fetchAllNewestFirst();

        // Assert
        $this->assertCount(1, $posts);

        $this->assertEquals(
            new PostInfo(
                id: $post->id,
                userId: $post->userId,
                userName: $post->userName,
                phoneNumber: $post->phoneNumber,
                description: $post->description,
                link: $post->link,
                pinColor: $post->pinColor,
                createdAt: $post->createdAt,
                expiresAt: $post->expiresAt,
            ),
            $posts[0]
        );
    }

    public function testDeletePost(): void
    {
        // Arrange
        $user = self::addAuthenticatedUser()[0];
        $post = self::createPost($user);

        // Act
        PostService::deletePost($user, $post->id);

        // Assert
        $postsAfterDelete = PostService::fetchAllNewestFirst();
        $this->assertCount(0, $postsAfterDelete);
    }

    public function testAdminCanDeleteUserPost(): void
    {
        // Arrange
        $admin = self::addAuthenticatedAdmin()[0];
        $user = self::addAuthenticatedUser()[0];
        $post = self::createPost($user);

        // Act
        PostService::deletePost($admin, $post->id);

        // Assert
        $postsAfterDelete = PostService::fetchAllNewestFirst();
        $this->assertCount(0, $postsAfterDelete);
    }

    public function testUserCantDeleteAnotherUsersPost(): void
    {
        $user1 = self::addAuthenticatedUser()[0];
        $user2 = self::addAuthenticatedUser()[0];

        $post = self::createPost($user1);

        // Act
        try {
            PostService::deletePost($user2, $post->id);
        } catch (\Exception $e) {
            // Assert
            self::assertEquals("You do not have permission to delete this post.", $e->getMessage());
        }
    }

    public function testFailsToCreateMoreThanMaxPosts(): void
    {
        // Arrange
        $user = self::addAuthenticatedUser()[0];
        $maxPosts = $user->maxActivePosts;

        // Create max number of posts
        for ($i = 0; $i < $maxPosts; $i++) {
            self::createPost($user);
        }

        // Act
        try {
            self::createPost($user);
            $this->fail("Should have thrown an exception for exceeding max posts");
        } catch (\Exception $e) {
            // Assert
            $this->assertEquals("You cannot have more than {$maxPosts} posts at once.", $e->getMessage());
        }
    }

    public function testUserWithCustomMaxPostsLimit(): void
    {
        // Arrange
        $customMaxPosts = 5;
        $user = self::addCustomAuthenticatedUser(
            name: self::$faker->firstName(),
            email: self::$faker->email(),
            phoneNumber: self::$faker->phoneNumber(),
            maxActivePosts: $customMaxPosts
        )[0];
        
        // Create posts up to the custom limit
        for ($i = 0; $i < $customMaxPosts; $i++) {
            self::createPost($user);
        }
        
        // Act
        try {
            self::createPost($user);
            $this->fail("Should have thrown an exception for exceeding custom max posts");
        } catch (\Exception $e) {
            // Assert
            $this->assertEquals("You cannot have more than {$customMaxPosts} posts at once.", $e->getMessage());
        }
    }

    private static function createPost($user)
    {
        return PostService::createPost(
            user: $user,
            description: self::$faker->text(),
            pinColor: self::$faker->pinColor(),
            link: self::$faker->url(),
            expiresAt: self::$faker->nearingDate()
        );
    }
}

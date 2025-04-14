<?php

declare(strict_types=1);

namespace App\Tests\Suites\Services;

use App\Core\MariaTransactor;
use App\Core\Types\Moment;
use App\Domain\Models\PostInfo;
use App\Domain\Repositories\PostRepo;
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

        $post = PostService::createPost($user, $description, $pinColor, $link, Moment::apply($expiresAt));

        // Assert
        $this->assertEquals($name, $post->userName);
        $this->assertEquals($description, $post->description);
        $this->assertEquals($phoneNumber, $post->phoneNumber);
        $this->assertEquals($link, $post->link);
        $this->assertEquals($pinColor, $post->pinColor);
        $this->assertEquals($expiresAt, $post->expiresAt);
        $this->assertEquals(null, $post->resolvedAt);
    }

    public function testGetAllPosts(): void
    {
        // Arrange
        $user = self::addAuthenticatedUser()[0];

        // Act
        $post = self::createPost($user);
        $posts = PostService::fetchNewestFirstAndResolvedLast();

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
                resolvedAt: $post->resolvedAt,
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
        $postsAfterDelete = PostService::fetchNewestFirstAndResolvedLast();
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
        $postsAfterDelete = PostService::fetchNewestFirstAndResolvedLast();
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
            $this->assertEquals("You cannot have more than $maxPosts active posts at once.", $e->getMessage());
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
            $this->assertEquals("You cannot have more than $customMaxPosts active posts at once.", $e->getMessage());
        }
    }

    public function testUserCanResolvePost(): void
    {
        // Arrange
        $user = self::addAuthenticatedUser()[0];
        $post = self::createPost($user);

        // Act
        PostService::resolvePost($user, $post->id);

        // Assert
        $post = PostRepo::selectPostById($post->id);
        $this->assertNotNull($post->resolvedAt);
    }

    public function testAdminCantResolvePost(): void
    {
        // Arrange
        $admin = self::addAuthenticatedAdmin()[0];
        $user = self::addAuthenticatedUser()[0];
        $post = self::createPost($user);

        // Act
        try {
            PostService::resolvePost($admin, $post->id);
        } catch (\Exception $e) {
            // Assert
            $this->assertEquals("Failed to find post $post->id.", $e->getMessage());
        }
    }

    public function testUserCanCreateMaxPostsAfterResolvingOne(): void
    {
        $this->expectNotToPerformAssertions();

        // Arrange
        $user = self::addAuthenticatedUser()[0];
        $maxPosts = $user->maxActivePosts;

        for ($i = 0; $i < $maxPosts; $i++) {
            $post = self::createPost($user);
        }

        // Act
        PostService::resolvePost($user, $post->id);

        try {
            self::createPost($user);
        } catch (\Exception $_) {
            $this->fail("Should not have thrown an exception for exceeding max posts");
        }
    }

    public function testUserCantResolveHisOwnPostTwice(): void
    {
        // Arrange
        $user = self::addAuthenticatedUser()[0];
        $post = self::createPost($user);

        PostService::resolvePost($user, $post->id);

        // Act
        try {
            PostService::resolvePost($user, $post->id);
            $this->fail('Should have thrown an exception for resolving post twice');
        } catch (\Exception $e) {
            $this->assertEquals('This post is already resolved.', $e->getMessage());
        }
    }

    public function testPostsAreSortedByNewestFirstAndResolvedLast(): void
    {
        // Arrange
        $user1 = self::addAuthenticatedUser()[0];
        $user2 = self::addAuthenticatedUser()[0];
        $user3 = self::addAuthenticatedUser()[0];

        $post11 = self::createPost($user1);
        usleep(1000);
        $post21 = self::createPost($user2);
        usleep(1000);
        $post31 = self::createPost($user3);
        usleep(1000);
        $post12 = self::createPost($user1);
        usleep(1000);
        $post22 = self::createPost($user2);

        PostService::resolvePost($user3, $post31->id);
        usleep(1000); // Resolving is faster than creation :/ I don't want to use DATETIME(4); skip 1 ms
        PostService::resolvePost($user1, $post11->id);

        // Act
        $posts = PostService::fetchNewestFirstAndResolvedLast();

        // Assert
        $expectedPostsOrder = [$post22->id, $post12->id, $post21->id, $post11->id, $post31->id];
        $this->assertEquals($expectedPostsOrder, array_column($posts, 'id'));
    }

    public function testOnlyTwoResolvedPostsKeptPerUser(): void
    {
        // Arrange
        $user = self::addAuthenticatedUser()[0];

        // Act
        $post1 = self::createPost($user);
        usleep(1000);
        $post2 = self::createPost($user);
        PostService::resolvePost($user, $post1->id);
        usleep(1000);
        PostService::resolvePost($user, $post2->id);
        usleep(1000);
        $post3 = self::createPost($user);
        PostService::resolvePost($user, $post3->id);

        $posts = PostService::fetchNewestFirstAndResolvedLast();

        // Assert
        $userResolvedPosts = array_filter($posts, function ($post) use ($user) {
            return $post->userId === $user->id && $post->resolvedAt !== null;
        });

        $postIds = array_column($userResolvedPosts, 'id');
        $this->assertCount(2, $postIds);
        $this->assertContains($post2->id, $postIds);
        $this->assertContains($post3->id, $postIds);
    }

    private static function createPost($user)
    {
        return PostService::createPost(
            user: $user,
            description: self::$faker->text(),
            pinColor: self::$faker->pinColor(),
            link: self::$faker->url(),
            expiresAt: Moment::apply(self::$faker->nearingDate())
        );
    }
}

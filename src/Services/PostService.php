<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Exceptions\Anomaly;
use App\Core\Exceptions\Forbidden;
use App\Core\Exceptions\InvalidState;
use App\Core\Types\Moment;
use App\Domain\Models\Post;
use App\Domain\Models\PostInfo;
use App\Domain\Models\UserInfo;
use App\Domain\Repositories\PostRepo;
use App\Exceptions\PostNotFound;

final class PostService
{
    /**
     * @throws \Exception
     */
    public static function createPost(
        UserInfo $user,
        string   $description,
        string   $pinColor,
        ?string  $link,
        Moment   $expiresAt
    ): PostInfo
    {
        $userActivePostsCount = PostRepo::selectCountUnresolvedByUser($user->id);
        if ($userActivePostsCount >= $user->maxActivePosts) {
            throw new InvalidState("You cannot have more than $user->maxActivePosts active posts at once.");
        }

        $encryptedDescription = Crypto::encrypt($description);
        $encryptedLink = $link ? Crypto::encrypt($link) : null;

        $postId = PostRepo::insertPost(
            userId: $user->id,
            encryptedDescription: $encryptedDescription,
            encryptedLink: $encryptedLink,
            pinColor: $pinColor,
            createdAt: Moment::now(),
            expiresAt: $expiresAt
        );

        if (!$postId) {
            throw new Anomaly('Failed to create post.');
        }

        $post = PostRepo::selectPostById($postId);
        if (!$post) {
            throw new PostNotFound($postId);
        }

        return new PostInfo(
            id: $post->id,
            userId: $post->userId,
            userName: Crypto::decrypt($post->encryptedUserName),
            phoneNumber: Crypto::decrypt($post->encryptedPhoneNumber),
            description: Crypto::decrypt($post->encryptedDescription),
            link: $post->encryptedLink ? Crypto::decrypt($post->encryptedLink) : null,
            pinColor: $post->pinColor,
            createdAt: $post->createdAt,
            expiresAt: $post->expiresAt,
            resolvedAt: $post->resolvedAt
        );
    }

    /**
     * @return PostInfo[]
     * @throws \Exception
     */
    public static function fetchNewestFirstAndResolvedLast(): array
    {
        $posts = PostRepo::selectOrderedByLatestFirstAndResolvedLast();

        return array_map(fn($post) => new PostInfo(
            id: $post->id,
            userId: $post->userId,
            userName: Crypto::decrypt($post->encryptedUserName),
            phoneNumber: Crypto::decrypt($post->encryptedPhoneNumber),
            description: Crypto::decrypt($post->encryptedDescription),
            link: $post->encryptedLink ? Crypto::decrypt($post->encryptedLink) : null,
            pinColor: $post->pinColor,
            createdAt: $post->createdAt,
            expiresAt: $post->expiresAt,
            resolvedAt: $post->resolvedAt
        ), $posts);
    }

    /**
     * @throws Forbidden
     * @throws \Exception
     */
    public static function deletePost(UserInfo $user, int $postId): void
    {
        $post = PostRepo::selectPostById($postId);
        if (!$post) {
            throw new PostNotFound($postId);
        }

        if ($post->userId !== $user->id && !$user->isAdmin()) {
            throw new Forbidden('You do not have permission to delete this post.');
        }

        if (!PostRepo::deletePostById($postId)) {
            throw new Anomaly('Failed to delete post.');
        }
    }

    /**
     * @throws Forbidden
     * @throws \Exception
     */
    public static function resolvePost(UserInfo $user, int $postId): void
    {
        $posts = PostRepo::selectPostsForUser($user->id);

        $post = array_reduce($posts, function (?Post $foundPost, Post $currentPost) use ($postId) {
            return $foundPost ?? ($currentPost->id === $postId ? $currentPost : null);
        });

        if (!$post) {
            throw new PostNotFound($postId);
        }

        if ($post->resolvedAt) {
            throw new InvalidState('This post is already resolved.');
        }

        if (!PostRepo::resolvePostById($postId, Moment::now())) {
            throw new Anomaly('Failed to resolve post.');
        }

        $existingResolvedPosts = array_filter($posts, fn (Post $post): bool => $post->resolvedAt !== null);

        if (count($existingResolvedPosts) >= 2) {
            usort($existingResolvedPosts, function (Post $a, Post $b) {
                // string cast because it's already not null due to the filtering
                return new \DateTime((string)$b->resolvedAt) <=> new \DateTime((string)$a->resolvedAt);
            });

            $oldestResolvedIds = array_map(fn(Post $post) => $post->id, array_slice($existingResolvedPosts, 1));

            if (!PostRepo::deletePostsByIds($oldestResolvedIds)) {
                throw new Anomaly('Failed to delete old resolved posts.');
            }
        }
    }
}

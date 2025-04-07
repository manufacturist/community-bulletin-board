<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Exceptions\Anomaly;
use App\Core\Exceptions\Forbidden;
use App\Core\Exceptions\InvalidState;
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
        string   $expiresAt
    ): PostInfo
    {
        $userActivePostsCount = PostRepo::selectCountByUser($user->id);
        if ($userActivePostsCount >= $user->maxActivePosts) {
            throw new InvalidState("You cannot have more than $user->maxActivePosts posts at once.");
        }

        $encryptedDescription = Crypto::encrypt($description);
        $encryptedLink = $link ? Crypto::encrypt($link) : null;

        $postId = PostRepo::insertPost($user->id, $encryptedDescription, $encryptedLink, $pinColor, $expiresAt);
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
            expiresAt: $post->expiresAt
        );
    }

    /**
     * @return PostInfo[]
     * @throws \Exception
     */
    public static function fetchAllNewestFirst(): array
    {
        $posts = PostRepo::selectOrderedByLatest();

        return array_map(fn($post) => new PostInfo(
            id: $post->id,
            userId: $post->userId,
            userName: Crypto::decrypt($post->encryptedUserName),
            phoneNumber: Crypto::decrypt($post->encryptedPhoneNumber),
            description: Crypto::decrypt($post->encryptedDescription),
            link: $post->encryptedLink ? Crypto::decrypt($post->encryptedLink) : null,
            pinColor: $post->pinColor,
            createdAt: $post->createdAt,
            expiresAt: $post->expiresAt
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
}

<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Core\MariaTransactor;
use App\Core\Types\Binary;
use App\Core\Types\Moment;
use App\Domain\Models\Post;

final class PostRepo
{
    public static function insertPost(
        int     $userId,
        Binary  $encryptedDescription,
        ?Binary $encryptedLink,
        string  $pinColor,
        Moment  $createdAt,
        Moment  $expiresAt
    ): ?int
    {
        $query = "
            INSERT INTO posts (user_id, encrypted_description, encrypted_link, pin_color, created_at, expires_at) 
            VALUES (:user_id, :encrypted_description, :encrypted_link, :pin_color, :created_at, :expires_at)
        ";

        $params = [
            ':user_id' => $userId,
            ':encrypted_description' => $encryptedDescription->value,
            ':encrypted_link' => $encryptedLink?->value,
            ':pin_color' => $pinColor,
            ':created_at' => $createdAt->value,
            ':expires_at' => $expiresAt->value,
        ];

        if (MariaTransactor::update($query, $params)) {
            $result = MariaTransactor::unique("SELECT LAST_INSERT_ID() AS id");
            return isset($result) && is_int($result['id']) ? $result['id'] : null;
        }

        return null;
    }

    public static function selectPostById(int $id): ?Post
    {
        $query = "
            SELECT 
                a.id, a.user_id, u.encrypted_name, u.encrypted_phone_number, 
                a.encrypted_description, a.encrypted_link, a.pin_color, 
                a.created_at, a.expires_at, a.resolved_at
            FROM posts a
            JOIN users u ON a.user_id = u.id
            WHERE a.id = :id
        ";

        $params = [':id' => $id];

        $result = MariaTransactor::unique($query, $params);
        return $result ? Post::fromRow($result) : null;
    }

    /** @return Post[] */
    public static function selectPostsForUser(int $userId): array
    {
        $query = "
            SELECT 
                a.id, a.user_id, u.encrypted_name, u.encrypted_phone_number, 
                a.encrypted_description, a.encrypted_link, a.pin_color, 
                a.created_at, a.expires_at, a.resolved_at
            FROM posts a
            JOIN users u ON a.user_id = u.id
            WHERE a.user_id = :user_id
        ";

        $params = [':user_id' => $userId];

        $results = MariaTransactor::query($query, $params);
        return array_map(fn($row) => Post::fromRow($row), $results);
    }

    /** @return Post[] */
    public static function selectOrderedByLatestFirstAndResolvedLast(): array
    {
        $query = "
            SELECT 
                a.id, a.user_id, u.encrypted_name, u.encrypted_phone_number, 
                a.encrypted_description, a.encrypted_link, a.pin_color, 
                a.created_at, a.expires_at, a.resolved_at
            FROM posts a
            JOIN users u ON a.user_id = u.id
            ORDER BY 
                resolved_at IS NULL DESC,
                resolved_at DESC,
                created_at DESC;
        ";

        $results = MariaTransactor::query($query);
        return array_map(fn($row) => Post::fromRow($row), $results);
    }

    public static function selectCountUnresolvedByUser(int $userId): int
    {
        $query = "SELECT COUNT(*) AS post_count FROM posts WHERE user_id = :user_id AND resolved_at IS NULL";
        $params = [':user_id' => $userId];

        $userPostsCount = MariaTransactor::unique($query, $params)['post_count'] ?? null;
        if (!is_int($userPostsCount)) {
            throw new \PDOException('Post count is not int.');
        }

        return $userPostsCount;
    }

    public static function deletePostById(int $postId): bool
    {
        $query = "DELETE FROM posts WHERE id = :post_id";
        $params = [':post_id' => $postId];

        return MariaTransactor::update($query, $params);
    }

    /** @param array<int> $postIds */
    public static function deletePostsByIds(array $postIds): bool
    {
        $query = "DELETE FROM posts WHERE id IN (:post_ids)";
        $params = [':post_ids' => implode(',', $postIds)];

        return MariaTransactor::update($query, $params);
    }

    public static function deleteOlderResolvedPosts(int $daysSinceResolved): bool
    {
        $query = "
            DELETE FROM posts 
            WHERE 
                resolved_at IS NOT NULL AND 
                resolved_at < DATE_SUB(NOW(), INTERVAL :days_since_resolved DAY)
        ";

        $params = [':days_since_resolved' => $daysSinceResolved];

        return MariaTransactor::update($query, $params);
    }


    public static function resolvePostById(int $postId, Moment $resolvedAt): bool
    {
        $query = "UPDATE posts SET resolved_at = :resolved_at WHERE id = :post_id AND resolved_at IS NULL";
        $params = [':post_id' => $postId, ':resolved_at' => $resolvedAt->value];

        return MariaTransactor::update($query, $params);
    }
}

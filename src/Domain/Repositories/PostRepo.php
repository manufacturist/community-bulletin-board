<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Core\MariaTransactor;
use App\Core\Types\Binary;
use App\Domain\Models\Post;

final class PostRepo
{
    public static function insertPost(
        int     $userId,
        Binary  $encryptedDescription,
        ?Binary $encryptedLink,
        string  $pinColor,
        string  $expiresAt
    ): ?int
    {
        $query = "
            INSERT INTO posts (user_id, encrypted_description, encrypted_link, pin_color, expires_at) 
            VALUES (:user_id, :encrypted_description, :encrypted_link, :pin_color, :expires_at)
        ";

        $params = [
            ':user_id' => $userId,
            ':encrypted_description' => $encryptedDescription->value,
            ':encrypted_link' => $encryptedLink?->value,
            ':pin_color' => $pinColor,
            ':expires_at' => $expiresAt,
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
                a.created_at, a.expires_at
            FROM posts a
            JOIN users u ON a.user_id = u.id
            WHERE a.id = :id
        ";

        $params = [':id' => $id];

        $result = MariaTransactor::unique($query, $params);
        return $result ? Post::fromRow($result) : null;
    }

    /** @return Post[] */
    public static function selectOrderedByLatest(): array
    {
        $query = "
            SELECT 
                a.id, a.user_id, u.encrypted_name, u.encrypted_phone_number, 
                a.encrypted_description, a.encrypted_link, a.pin_color, 
                a.created_at, a.expires_at
            FROM posts a
            JOIN users u ON a.user_id = u.id
            ORDER BY a.created_at DESC
        ";

        $results = MariaTransactor::query($query);
        return array_map(fn($row) => Post::fromRow($row), $results);
    }

    public static function selectCountByUser(int $userId): int
    {
        $query = "SELECT COUNT(*) AS post_count FROM posts WHERE user_id = :user_id";
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
}

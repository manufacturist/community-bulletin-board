<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Core\MariaTransactor;
use App\Core\Types\Binary;
use App\Domain\Models\User;

final class UserRepo
{
    public static function insertUser(
        Binary $encryptedName,
        Binary $encryptedEmail,
        Binary $encryptedPhoneNumber,
        Binary $emailHash,
        string $passwordHash,
        int    $maxActivePosts,
        string $role,
    ): bool
    {
        $query = "
            INSERT INTO users (
                encrypted_name, encrypted_email, encrypted_phone_number, 
                email_hash, password_hash, max_active_posts, role
            ) 
            VALUES (
                :encrypted_name, :encrypted_email, :encrypted_phone_number, 
                :email_hash, :password_hash, :max_active_posts, :role
            )
        ";

        $params = [
            ':encrypted_name' => $encryptedName->value,
            ':encrypted_email' => $encryptedEmail->value,
            ':encrypted_phone_number' => $encryptedPhoneNumber->value,
            ':email_hash' => $emailHash->value,
            ':password_hash' => $passwordHash,
            ':max_active_posts' => $maxActivePosts,
            ':role' => $role,
        ];

        return MariaTransactor::update($query, $params);
    }

    public static function selectUserByEmailHash(Binary $emailHash): ?User
    {
        $query = "SELECT * FROM users WHERE email_hash = :email_hash";
        $params = [':email_hash' => $emailHash->value];

        $result = MariaTransactor::unique($query, $params);
        return $result ? User::fromRow($result) : null;
    }

    /** @return User[] */
    public static function selectAll(): array
    {
        $query = 'SELECT * FROM users';
        $results = MariaTransactor::query($query);
        return array_map(fn($row) => User::fromRow($row), $results);
    }

    public static function selectUserById(int $userId): ?User
    {
        $query = "SELECT * FROM users WHERE id = :user_id";
        $params = [':user_id' => $userId];

        $result = MariaTransactor::unique($query, $params);
        return $result ? User::fromRow($result) : null;
    }

    public static function selectCount(): int
    {
        $query = "SELECT COUNT(*) AS user_count FROM users";
        $result = MariaTransactor::unique($query);

        if (isset($result['user_count']) && is_int($result['user_count'])) {
            return $result['user_count'];
        }

        return 0;
    }

    public static function deleteUserById(int $userId): bool
    {
        $query = "DELETE FROM users WHERE id = :user_id";
        $params = [':user_id' => $userId];

        return MariaTransactor::update($query, $params);
    }

    public static function updateMaxActivePosts(int $userId, int $maxActivePosts): bool
    {
        $query = "UPDATE users SET max_active_posts = :max_active_posts WHERE id = :user_id";
        $params = [':max_active_posts' => $maxActivePosts, ':user_id' => $userId,];

        return MariaTransactor::update($query, $params);
    }

    public static function updateRole(int $userId, string $role): bool
    {
        $query = "UPDATE users SET role = :role WHERE id = :user_id";
        $params = [':role' => $role, ':user_id' => $userId,];

        return MariaTransactor::update($query, $params);
    }
}

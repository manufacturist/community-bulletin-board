<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Core\MariaTransactor;
use App\Core\Types\Binary;
use App\Domain\Models\User;

final class AuthenticationRepo
{
    public static function insertAuthentication(Binary $tokenHash, int $userId, string $expiresAt): bool
    {
        $query = "
            INSERT INTO authentications (token_hash, user_id, expires_at)
            VALUES (:token_hash, :user_id, :expires_at)
        ";

        $params = [
            ':token_hash' => $tokenHash->value,
            ':user_id' => $userId,
            ':expires_at' => $expiresAt,
        ];

        return MariaTransactor::update($query, $params);
    }

    public static function selectUserByTokenHash(Binary $tokenHash): ?User
    {
        $query = "
            SELECT u.* 
            FROM authentications a
            JOIN users u ON a.user_id = u.id
            WHERE 
                a.token_hash = :token_hash AND 
                a.expires_at > NOW()
            LIMIT 1
        ";

        $params = [':token_hash' => $tokenHash->value];

        $result = MariaTransactor::unique($query, $params);
        return $result ? User::fromRow($result) : null;
    }

    public static function deleteAuthenticationByTokenHash(Binary $tokenHash): bool
    {
        $query = "DELETE FROM authentications WHERE token_hash = :token_hash ";
        $params = [':token_hash' => $tokenHash->value];

        return MariaTransactor::update($query, $params);
    }

    public static function deleteExpiredTokens(): bool
    {
        $query = "DELETE FROM authentications WHERE expires_at < NOW()";
        return MariaTransactor::update($query);
    }
}

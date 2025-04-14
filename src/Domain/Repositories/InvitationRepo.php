<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Core\MariaTransactor;
use App\Core\Types\Binary;
use App\Core\Types\Moment;
use App\Domain\Models\Invitation;

final class InvitationRepo
{
    public static function insertInvitation(
        string $email,
        Binary $invitationToken,
        bool   $isAdmin,
        Moment $createdAt
    ): bool
    {
        $query = "
        INSERT INTO invitations (email, token, is_admin, created_at) 
        VALUES (:email, :token, :is_admin, :created_at)
    ";

        $params = [
            ':email' => $email,
            ':token' => $invitationToken->value,
            ':is_admin' => $isAdmin ? 1 : 0,
            ':created_at' => $createdAt->value
        ];

        return MariaTransactor::update($query, $params);
    }

    /**
     * @return Invitation[]
     */
    public static function selectAll(): array
    {
        $query = "SELECT * FROM invitations";
        $results = MariaTransactor::query($query);
        return array_map(fn($row) => Invitation::fromRow($row), $results);
    }

    public static function selectInvitationByToken(Binary $invitationToken): ?Invitation
    {
        $query = "SELECT * FROM invitations WHERE token = :token";
        $params = [':token' => $invitationToken->value];

        $result = MariaTransactor::unique($query, $params);
        return $result ? Invitation::fromRow($result) : null;
    }

    public static function selectInvitationByEmail(string $email): ?Invitation
    {
        $query = "SELECT * FROM invitations WHERE email = :email";
        $params = [':email' => $email];

        $result = MariaTransactor::unique($query, $params);
        return $result ? Invitation::fromRow($result) : null;
    }

    public static function deleteInvitationByToken(Binary $invitationToken): bool
    {
        $query = "DELETE FROM invitations WHERE token = :token";
        $params = [':token' => $invitationToken->value];

        return MariaTransactor::update($query, $params);
    }

    public static function deleteInvitationByEmail(string $email): bool
    {
        $query = "DELETE FROM invitations WHERE email = :email";
        $params = [':email' => $email];

        return MariaTransactor::update($query, $params);
    }

    public static function deleteExpiredInvitations(): bool
    {
        $query = "DELETE FROM invitations WHERE created_at < (NOW() - INTERVAL 1 DAY)";
        return MariaTransactor::update($query);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Core\Types\Binary;

final class Invitation
{
    public int $id;
    public string $email;
    public Binary $token;
    public bool $isAdmin;
    public ?bool $isDelivered;
    public \DateTime $createdAt;

    public function __construct(
        int       $id,
        string    $email,
        Binary    $token,
        bool      $isAdmin,
        ?bool     $isDelivered,
        \DateTime $createdAt
    )
    {
        $this->id = $id;
        $this->email = $email;
        $this->token = $token;
        $this->isAdmin = $isAdmin;
        $this->isDelivered = $isDelivered;
        $this->createdAt = $createdAt;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): Invitation
    {
        $id = is_int($row['id'])
            ? $row['id']
            : throw new \InvalidArgumentException("Missing or invalid key: id");

        $email = is_string($row['email'])
            ? $row['email']
            : throw new \InvalidArgumentException("Missing or invalid key: email");

        $token = is_string($row['token'])
            ? Binary::apply($row['token'])
            : throw new \InvalidArgumentException("Missing or invalid key: token");

        $isAdmin = is_int($row['is_admin'])
            ? $row['is_admin'] == 1
            : throw new \InvalidArgumentException("Missing or invalid key: is_admin");

        $isDelivered = array_key_exists('is_delivered', $row) && is_int($row['is_delivered'])
            ? $row['is_delivered'] == 1
            : null;

        if (!isset($row['created_at']) || !is_string($row['created_at'])) {
            throw new \InvalidArgumentException("Missing or invalid key: created_at");
        }

        $createdAt = \DateTime::createFromFormat('Y-m-d H:i:s', $row['created_at']);

        if (!$createdAt) {
            throw new \InvalidArgumentException("Invalid date format for created_at");
        }

        return new Invitation(
            id: $id,
            email: $email,
            token: $token,
            isAdmin: $isAdmin,
            isDelivered: $isDelivered,
            createdAt: $createdAt
        );
    }
}

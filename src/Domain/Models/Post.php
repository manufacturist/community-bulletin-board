<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Core\Types\Binary;

final class Post
{
    public int $id;
    public int $userId;
    public Binary $encryptedUserName;
    public Binary $encryptedPhoneNumber;
    public Binary $encryptedDescription;
    public ?Binary $encryptedLink;
    public string $pinColor;
    public string $createdAt;
    public string $expiresAt;

    public function __construct(
        int     $id,
        int     $userId,
        Binary  $encryptedUserName,
        Binary  $encryptedPhoneNumber,
        Binary  $encryptedDescription,
        ?Binary $encryptedLink,
        string  $pinColor,
        string  $createdAt,
        string  $expiresAt
    )
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->encryptedUserName = $encryptedUserName;
        $this->encryptedPhoneNumber = $encryptedPhoneNumber;
        $this->encryptedDescription = $encryptedDescription;
        $this->encryptedLink = $encryptedLink;
        $this->pinColor = $pinColor;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): Post
    {
        $id = is_int($row['id'])
            ? $row['id']
            : throw new \InvalidArgumentException("Missing / Invalid key: id");

        $userId = is_int($row['user_id'])
            ? $row['user_id']
            : throw new \InvalidArgumentException("Missing / Invalid key: user_id");

        $encryptedUserName = is_string($row['encrypted_name'])
            ? Binary::apply($row['encrypted_name'])
            : throw new \InvalidArgumentException("Missing / Invalid key: encrypted_name");

        $encryptedPhoneNumber = is_string($row['encrypted_phone_number'])
            ? Binary::apply($row['encrypted_phone_number'])
            : throw new \InvalidArgumentException("Missing / Invalid key: encrypted_phone_number");

        $encryptedDescription = is_string($row['encrypted_description'])
            ? Binary::apply($row['encrypted_description'])
            : throw new \InvalidArgumentException("Missing / Invalid key: encrypted_description");

        $encryptedLink = array_key_exists('encrypted_link', $row) && is_string($row['encrypted_link'])
            ? Binary::apply($row['encrypted_link'])
            : null;

        $pinColor = is_string($row['pin_color'])
            ? $row['pin_color']
            : throw new \InvalidArgumentException("Missing / Invalid key: pin_color");

        $createdAt = is_string($row['created_at'])
            ? $row['created_at']
            : throw new \InvalidArgumentException("Missing / Invalid key: created_at");

        $expiresAt = is_string($row['expires_at'])
            ? $row['expires_at']
            : throw new \InvalidArgumentException("Missing / Invalid key: expires_at");

        return new Post(
            id: $id,
            userId: $userId,
            encryptedUserName: $encryptedUserName,
            encryptedPhoneNumber: $encryptedPhoneNumber,
            encryptedDescription: $encryptedDescription,
            encryptedLink: $encryptedLink,
            pinColor: $pinColor,
            createdAt: $createdAt,
            expiresAt: $expiresAt
        );
    }
}

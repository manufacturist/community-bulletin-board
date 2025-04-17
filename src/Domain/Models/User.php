<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Core\Types\Binary;

final class User
{
    public int $id;
    public Binary $encryptedName;
    public Binary $encryptedEmail;
    public Binary $encryptedPhoneNumber;
    public Binary $emailHash;
    public string $passwordHash;
    public int $maxActivePosts;
    public string $theme;
    public string $role;

    public function __construct(
        int    $id,
        Binary $encryptedName,
        Binary $encryptedEmail,
        Binary $encryptedPhoneNumber,
        Binary $emailHash,
        string $passwordHash,
        int    $maxActivePosts,
        string $theme,
        string $role
    )
    {
        $this->id = $id;
        $this->encryptedName = $encryptedName;
        $this->encryptedEmail = $encryptedEmail;
        $this->encryptedPhoneNumber = $encryptedPhoneNumber;
        $this->emailHash = $emailHash;
        $this->passwordHash = $passwordHash;
        $this->maxActivePosts = $maxActivePosts;
        $this->theme = $theme;
        $this->role = $role;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(mixed $row): User
    {
        $id = is_int($row['id'])
            ? $row['id']
            : throw new \InvalidArgumentException("Missing / Invalid key: id");

        $encryptedName = is_string($row['encrypted_name'])
            ? Binary::apply($row['encrypted_name'])
            : throw new \InvalidArgumentException("Missing / Invalid key: encrypted_name");

        $encryptedEmail = is_string($row['encrypted_email'])
            ? Binary::apply($row['encrypted_email'])
            : throw new \InvalidArgumentException("Missing / Invalid key: encrypted_email");

        $encryptedPhoneNumber = is_string($row['encrypted_phone_number'])
            ? Binary::apply($row['encrypted_phone_number'])
            : throw new \InvalidArgumentException("Missing / Invalid key: encrypted_phone_number");

        $emailHash = is_string($row['email_hash'])
            ? Binary::apply($row['email_hash'])
            : throw new \InvalidArgumentException("Missing / Invalid key: email_hash");

        $passwordHash = is_string($row['password_hash'])
            ? $row['password_hash']
            : throw new \InvalidArgumentException("Missing / Invalid key: password_hash");

        $maxActivePosts = isset($row['max_active_posts'])
        && (is_int($row['max_active_posts']) || is_string($row['max_active_posts']))
            ? (int)$row['max_active_posts']
            : 2; // Default to 2 for backward compatibility with existing data

        $theme = isset($row['theme']) && is_string($row['theme'])
            ? $row['theme']
            : 'cork'; // Default to cork theme for backward compatibility

        $role = is_string($row['role'])
            ? $row['role']
            : throw new \InvalidArgumentException("Missing / Invalid key: role");

        return new User(
            id: $id,
            encryptedName: $encryptedName,
            encryptedEmail: $encryptedEmail,
            encryptedPhoneNumber: $encryptedPhoneNumber,
            emailHash: $emailHash,
            passwordHash: $passwordHash,
            maxActivePosts: $maxActivePosts,
            theme: $theme,
            role: $role
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Core\Crypto;

final class UserInfo
{
    public int $id;
    public string $name;
    public string $email;
    public string $phoneNumber;
    public int $maxActivePosts;
    public string $theme;
    public string $role;

    public function __construct(
        int    $id,
        string $name,
        string $email,
        string $phoneNumber,
        int    $maxActivePosts,
        string $theme,
        string $role
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->phoneNumber = $phoneNumber;
        $this->maxActivePosts = $maxActivePosts;
        $this->theme = $theme;
        $this->role = $role;
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return $this->isOwner() || $this->role === "admin";
    }

    public static function fromUser(User $user): self
    {
        return new UserInfo(
            $user->id,
            Crypto::decrypt($user->encryptedName),
            Crypto::decrypt($user->encryptedEmail),
            Crypto::decrypt($user->encryptedPhoneNumber),
            $user->maxActivePosts,
            $user->theme,
            $user->role
        );
    }
}

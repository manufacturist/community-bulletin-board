<?php

declare(strict_types=1);

namespace App\Domain\Models;

final class PostInfo
{
    public int $id;
    public int $userId;
    public string $userName;
    public string $phoneNumber;
    public string $description;
    public ?string $link;
    public string $pinColor;
    public string $createdAt;
    public string $expiresAt;

    public function __construct(
        int     $id,
        int     $userId,
        string  $userName,
        string  $phoneNumber,
        string  $description,
        ?string $link,
        string  $pinColor,
        string  $createdAt,
        string  $expiresAt
    )
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->userName = $userName;
        $this->phoneNumber = $phoneNumber;
        $this->description = $description;
        $this->link = $link;
        $this->pinColor = $pinColor;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
    }
}

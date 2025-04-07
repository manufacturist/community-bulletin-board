<?php

declare(strict_types=1);

namespace App\Controllers\RequestDTOs;

final readonly class NewPostDTO
{
    public function __construct(
        public string  $description,
        public string  $pinColor,
        public ?string $link,
        public string  $expiresAt
    )
    {
    }
}

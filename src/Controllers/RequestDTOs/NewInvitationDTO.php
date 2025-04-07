<?php

declare(strict_types=1);

namespace App\Controllers\RequestDTOs;

final readonly class NewInvitationDTO
{
    public function __construct(
        public string $email,
        public bool   $isAdmin
    )
    {
    }
}

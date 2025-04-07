<?php

declare(strict_types=1);

namespace App\Controllers\RequestDTOs;

final readonly class AcceptInvitationDTO
{
    public function __construct(
        public string $token,
        public string $name,
        public string $phoneNumber,
        public string $password
    )
    {
    }
}

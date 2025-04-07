<?php

declare(strict_types=1);

namespace App\Controllers\RequestDTOs;

final readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password
    )
    {
    }
}

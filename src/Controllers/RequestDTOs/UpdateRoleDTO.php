<?php

declare(strict_types=1);

namespace App\Controllers\RequestDTOs;

final readonly class UpdateRoleDTO
{
    public function __construct(public string $role)
    {
    }
}
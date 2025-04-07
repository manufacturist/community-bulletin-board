<?php

declare(strict_types=1);

namespace App\Controllers\RequestDTOs;

final readonly class UpdateMaxPostsDTO
{
    public function __construct(public int $adjustment)
    {
    }
}

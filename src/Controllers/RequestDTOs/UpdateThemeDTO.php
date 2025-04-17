<?php

declare(strict_types=1);

namespace App\Controllers\RequestDTOs;

final readonly class UpdateThemeDTO
{
    public function __construct(public string $theme)
    {
    }
}
<?php

namespace App\Exceptions;

use App\Core\Exceptions\NotFound;

final class UserNotFound extends NotFound
{
    public function __construct(int $userId)
    {
        parent::__construct("Failed to find user $userId.");
    }
}

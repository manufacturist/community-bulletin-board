<?php

namespace App\Exceptions;

use App\Core\Exceptions\Conflict;

final class EmailAlreadyUsedException extends Conflict
{
    public function __construct(string $message = 'Email already used by another user or invitation.')
    {
        parent::__construct($message);
    }
}

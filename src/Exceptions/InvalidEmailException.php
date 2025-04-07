<?php

namespace App\Exceptions;

use App\Core\Exceptions\InvalidState;

final class InvalidEmailException extends InvalidState
{
    public function __construct(string $message = 'Email is not valid.')
    {
        parent::__construct($message);
    }
}

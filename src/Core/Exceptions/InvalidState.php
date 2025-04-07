<?php

namespace App\Core\Exceptions;

class InvalidState extends \Exception
{
    public function __construct(string $message = 'Invalid State')
    {
        parent::__construct($message);
    }
}

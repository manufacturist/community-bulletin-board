<?php

namespace App\Core\Exceptions;

class NotFound extends \Exception
{
    public function __construct(string $message = 'Not Found')
    {
        parent::__construct($message);
    }
}

<?php

namespace App\Core\Exceptions;

class Conflict extends \Exception
{
    public function __construct(string $message = 'Conflict')
    {
        parent::__construct($message);
    }
}

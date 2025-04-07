<?php

namespace App\Core\Exceptions;

final class Forbidden extends \Exception
{
    public function __construct(string $message = 'Forbidden')
    {
        parent::__construct($message);
    }
}

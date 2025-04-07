<?php

namespace App\Core\Exceptions;

final class Expired extends \Exception
{
    public function __construct(string $message = 'Expired')
    {
        parent::__construct($message);
    }
}

<?php

namespace App\Core\Exceptions;

final class Unauthorized extends \Exception
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message);
    }
}

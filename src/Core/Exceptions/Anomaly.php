<?php

namespace App\Core\Exceptions;

class Anomaly extends \Exception
{
    public function __construct(string $message = 'Unexpected Anomaly')
    {
        parent::__construct($message);
    }
}

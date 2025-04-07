<?php

namespace App\Exceptions;

use App\Core\Exceptions\Anomaly;

final class EmailSendingException extends Anomaly
{
    public function __construct(string $message = 'Failed to send invitation email.')
    {
        parent::__construct($message);
    }
}

<?php

namespace App\Exceptions;

use App\Core\Exceptions\Anomaly;

final class InvitationCreationException extends Anomaly
{
    public function __construct(string $message = 'Failed to create invitation.')
    {
        parent::__construct($message);
    }
}

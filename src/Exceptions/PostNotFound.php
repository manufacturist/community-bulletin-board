<?php

namespace App\Exceptions;

use App\Core\Exceptions\NotFound;

final class PostNotFound extends NotFound
{
    public function __construct(int $postId)
    {
        parent::__construct("Failed to find post $postId.");
    }
}

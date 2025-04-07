<?php

declare(strict_types=1);

namespace App\Tests\TestKit\Faker;

use App\Core\Crypto;
use App\Core\Types\Binary;
use Faker\Provider\Base;

class Token extends Base
{
    public function token(): array
    {
        $newToken = Binary::apply(random_bytes(16));
        $newTokenHash = Crypto::hash($newToken->value);

        return [$newToken, $newTokenHash];
    }
}
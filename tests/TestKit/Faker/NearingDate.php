<?php

declare(strict_types=1);

namespace App\Tests\TestKit\Faker;

use Faker\Provider\Base;

class NearingDate extends Base
{
    public function nearingDate(): string
    {
        $hours = $this->generator->numberBetween(1, 12);

        return date('Y-m-d H:i:s', strtotime("+$hours hour"));
    }
}
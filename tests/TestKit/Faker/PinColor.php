<?php

declare(strict_types=1);

namespace App\Tests\TestKit\Faker;

use Faker\Provider\Base;

class PinColor extends Base
{
    private static $colors = ['red', 'blue', 'green', 'yellow', 'purple', 'pink'];

    public function pinColor(): string
    {
        return $this->generator->randomElement(self::$colors);
    }
}
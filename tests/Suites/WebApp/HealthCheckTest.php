<?php

declare(strict_types=1);

namespace App\Tests\Suites\WebApp;

use App\Tests\TestKit\WebApp\WebAppAPI;
use App\Tests\TestKit\TestCases\WebAppTestCase;

final class HealthCheckTest extends WebAppTestCase
{
    private static ?WebAppAPI $api = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$api = new WebAppAPI("http://127.0.0.1:" . self::$webAppPort);
    }

    public function testHealthCheck(): void
    {
        self::assertEquals("OK", self::$api->checkHealth());
    }
}

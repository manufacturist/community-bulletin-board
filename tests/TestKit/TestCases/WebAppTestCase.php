<?php

declare(strict_types=1);
declare(ticks=1);

namespace App\Tests\TestKit\TestCases;

use App\Tests\TestKit\ReleaseHandler;
use Testcontainers\Container\GenericContainer;
use Testcontainers\Container\StartedGenericContainer;

abstract class WebAppTestCase extends BaseTestCase
{
    private static ?StartedGenericContainer $webAppContainer = null;

    protected static ?int $webAppPort = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (is_null(self::$webAppContainer)) {
            $containerName = 'webapp-cbb';
            $exposedPort = 8000;

            self::$webAppContainer = new GenericContainer('community-bulletin-board')
                ->withName($containerName)
                ->withNetwork(self::$networkName)
                ->withHealthCheckCommand("curl -f $containerName:$exposedPort/api/public/health")
                ->withEnvironment([
                    "DB_HOST" => self::$mariaDBContainer->getName(),
                    "DB_PORT" => 3306,
                    "DB_NAME" => $_ENV['DB_NAME'] ?? 'bulletin_board',
                    "DB_USERNAME" => $_ENV['DB_USERNAME'] ?? 'bulletin_board_user',
                    "DB_PASSWORD" => $_ENV['DB_PASSWORD'] ?? 'bulletin_board_password',

                    "CRYPTO_ENCRYPTION_KEY" => $_ENV['CRYPTO_ENCRYPTION_KEY'] ?? 'dc9f213f27579efceb8d9217a2b0cb01',
                    "CRYPTO_HMAC_KEY" => $_ENV['CRYPTO_HMAC_KEY'] ?? 'ddc9328354f7adba69bc688e1f2c1',
                    "CRYPTO_PEPPER" => $_ENV['CRYPTO_PEPPER'] ?? 'a8bdd25d1137d083658540947a652917'
                ])
                ->withExposedPorts($exposedPort)
                ->start();

            self::$webAppPort = self::$webAppContainer->getFirstMappedPort();
        }

        ReleaseHandler::registerHandler(
            id: 'webapp',
            callable: self::releaseResources(...),
            priority: ReleaseHandler::PRIORITY_HIGH
        );
    }

    public static function releaseResources(): void
    {
        self::$webAppContainer->stop();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
    }
}


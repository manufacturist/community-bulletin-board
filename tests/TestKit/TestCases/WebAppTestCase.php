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
    private static ?string $webAppContainerName = null;

    protected static ?int $webAppPort = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (is_null(self::$webAppContainer)) {
            self::$webAppContainerName = 'webapp-cbb' . bin2hex(random_bytes(4));
            $exposedPort = 8000;

            self::$webAppContainer = new GenericContainer('community-bulletin-board')
                ->withName(self::$webAppContainerName)
                ->withNetwork(self::$networkName)
                ->withHealthCheckCommand("curl -f " . self::$webAppContainerName . ":$exposedPort/api/public/health")
                ->withEnvironment([
                    "DB_HOST" => self::$mariaDBContainer->getName(),
                    "DB_PORT" => 3306,
                    "DB_NAME" => $_ENV['DB_NAME'] ?? 'bulletin_board',
                    "DB_USERNAME" => $_ENV['DB_USERNAME'] ?? 'bulletin_board_user',
                    "DB_PASSWORD" => $_ENV['DB_PASSWORD'] ?? 'bulletin_board_password',

                    "CRYPTO_ENCRYPTION_KEY" => $_ENV['CRYPTO_ENCRYPTION_KEY'] ?? 'dc9f213f27579efceb8d9217a2b0cb01',
                    "CRYPTO_HMAC_KEY" => $_ENV['CRYPTO_HMAC_KEY'] ?? 'ddc9328354f7adba69bc688e1f2c1'
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


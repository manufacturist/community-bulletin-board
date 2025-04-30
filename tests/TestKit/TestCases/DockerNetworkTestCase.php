<?php

namespace App\Tests\TestKit\TestCases;

use App\Tests\TestKit\ReleaseHandler;
use Docker\API\Model\Network;
use Docker\API\Model\NetworksCreatePostBody;
use Docker\API\Runtime\Client\Client;
use Docker\Docker;
use PHPUnit\Framework\TestCase;

abstract class DockerNetworkTestCase extends TestCase
{
    private static ?Docker $dockerClient = null;
    private static ?Network $dockerNetwork = null;
    protected static ?string $networkName = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$dockerClient = Docker::create();

        try {
            self::$networkName = 'network-cbb-' . bin2hex(random_bytes(4));
            self::$dockerNetwork = self::$dockerClient->networkInspect(self::$networkName);
        } catch (\Exception $e) {
            error_log("Failed to get network! " . $e->getMessage());
        }

        if (is_null(self::$dockerNetwork)) {
            $networkCreate = new NetworksCreatePostBody();
            $networkCreate->setName(self::$networkName);
            $networkCreate->setDriver('bridge');

            $response = self::$dockerClient->networkCreate($networkCreate);
            self::$dockerNetwork = self::$dockerClient->networkInspect($response->getId());

            if (!$response->getId()) {
                throw new \RuntimeException("Failed to create docker network! " . $response->getStatusCode());
            }
        }

        ReleaseHandler::registerHandler(
            id: 'network',
            callable: self::releaseResources(...),
            priority: ReleaseHandler::PRIORITY_LOW
        );
    }

    public static function releaseResources(): void
    {
        $response = self::$dockerClient->networkDelete(self::$networkName, Client::FETCH_RESPONSE);

        if ($response->getStatusCode() != 204) {
            echo "Failed to release DockerNetwork resource; got {$response->getStatusCode()}\n";
        }
    }
}
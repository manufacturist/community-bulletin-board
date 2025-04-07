<?php

namespace App\Core;

use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;

final class JSON
{
    private static ?self $instance = null;

    private Serializer $serializer;

    private function __construct()
    {
        $this->serializer = SerializerBuilder::create()
            ->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy())
            ->build();
    }

    private static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     * @throws \Exception
     */
    public static function deserialize(string $json, string $className): object
    {
        $result = self::getInstance()->serializer->deserialize($json, $className, 'json');

        if (!is_object($result) || !($result instanceof $className)) {
            throw new \InvalidArgumentException("Invalid JSON deserialization.");
        }

        /** @var T $result */
        return $result;
    }
}

<?php

namespace App\Tests\TestKit;

final class ReleaseHandler
{
    public const int PRIORITY_HIGH = 0;
    public const int PRIORITY_MID = 1;
    public const int PRIORITY_LOW = 2;

    private static array $handlers = [];

    public static function registerHandler(string $id, callable $callable, int $priority): void
    {
        if (!in_array($priority, [self::PRIORITY_HIGH, self::PRIORITY_MID, self::PRIORITY_LOW], true)) {
            throw new \InvalidArgumentException("Invalid priority. Use existing constants.");
        }

        self::$handlers[$priority][$id] = $callable;
        ksort(self::$handlers);
    }

    public static function executeHandlers(): void
    {
        echo PHP_EOL . "================================================" . PHP_EOL;
        echo "=== Releasing resources in an orderly manner ===" . PHP_EOL;
        echo "================================================" . PHP_EOL . PHP_EOL;

        foreach (self::$handlers as $priority => $handlers) {
            echo "Releasing P$priority Resources" . PHP_EOL;

            foreach ($handlers as $id => $handler) {
                $message = "Releasing resource $id";
                echo "$message" . PHP_EOL;

                $handler();

                echo str_repeat("-", strlen($message)) . PHP_EOL;
            }

            echo PHP_EOL;
        }
    }
}

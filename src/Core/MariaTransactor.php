<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * update, query and unique are taken and adapted from the great Scala library, doobie!
 * https://typelevel.org/doobie
 */
final class MariaTransactor
{
    private static ?\PDO $pdo = null;

    private static function connect(): \PDO
    {
        if (self::$pdo === null) {
            $host = isset($_ENV['DB_HOST']) && is_string($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : '127.0.0.1';
            $port = isset($_ENV['DB_PORT']) && is_numeric($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : '3306';
            $dbName = isset($_ENV['DB_NAME']) && is_string($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'bulletin_board';
            $dbUser = isset($_ENV['DB_USERNAME']) && is_string($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : 'bulletin_board_user';
            $dbPass = isset($_ENV['DB_PASSWORD']) && is_string($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : 'bulletin_board_password';

            $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8";

            self::$pdo = new \PDO($dsn, $dbUser, $dbPass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        }

        return self::$pdo;
    }

    private static function getConnection(): \PDO
    {
        return self::connect();
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function update(string $query, array $params = []): bool
    {
        $pdo = self::getConnection();

        $statement = $pdo->prepare($query);
        if (!$statement) {
            throw new \PDOException('Failed to prepare statement.');
        }

        return $statement->execute($params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<array<string, mixed>>
     */
    public static function query(string $query, array $params = []): array
    {
        $pdo = self::getConnection();

        $statement = $pdo->prepare($query);
        if (!$statement) {
            throw new \PDOException('Failed to prepare statement.');
        }

        if ($statement->execute($params)) {
            /** @var array<array<string, mixed>> $result */
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        return $result ?? [];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    public static function unique(string $query, array $params = []): ?array
    {
        $pdo = self::getConnection();

        $statement = $pdo->prepare($query);
        if (!$statement) {
            throw new \PDOException('Failed to prepare statement.');
        }

        if ($statement->execute($params)) {
            /** @var array<string, mixed>|bool $result */
            $result = $statement->fetch(\PDO::FETCH_ASSOC);

            if (is_bool($result)) {
                return null;
            }

            return $result;
        }

        throw new \PDOException('Failed to execute statement.');
    }
}

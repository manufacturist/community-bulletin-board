<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Core\MariaTransactor;
use App\Core\Types\Binary;
use App\Core\Types\Moment;

final class MigrationRepo
{
    public static function checkIfTableExists(): bool
    {
        $query = "SELECT 1 AS table_exists FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'migrations'";
        $result = MariaTransactor::unique($query);

        if (isset($result['table_exists']) && is_int($result['table_exists'])) {
            return $result['table_exists'] == 1;
        }

        return false;
    }

    public static function insertMigration(string $name, Binary $hash, Moment $timestamp): void
    {
        $query = "INSERT INTO migrations (name, hash, timestamp) VALUES (:name, :hash, :timestamp)";
        $params = [':name' => $name, ':hash' => $hash->value, ':timestamp' => $timestamp->value];

        MariaTransactor::update($query, $params);
    }

    public static function checkIfMigrationExists(Binary $hash): bool
    {
        $query = "SELECT 1 AS migration_exists FROM migrations WHERE hash = :hash LIMIT 1";
        $params = [':hash' => $hash->value];

        $result = MariaTransactor::unique($query, $params);
        if (isset($result['migration_exists']) && is_int($result['migration_exists'])) {
            return $result['migration_exists'] == 1;
        }

        return false;
    }

    public static function getLatestMigrationId(): ?int
    {
        $query = "SELECT MAX(id) AS latest_migration_id FROM migrations";
        $result = MariaTransactor::unique($query);

        return isset($result['latest_migration_id']) && is_int($result['latest_migration_id'])
            ? $result['latest_migration_id']
            : null;
    }
}

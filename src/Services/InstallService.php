<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Exceptions\Anomaly;
use App\Core\MariaTransactor;
use App\Core\Types\Base64String;
use App\Core\Types\Binary;
use App\Core\Types\Moment;
use App\Domain\Repositories\InvitationRepo;
use App\Domain\Repositories\MigrationRepo;
use App\Exceptions\InvitationCreationException;
use Random\RandomException;

final class InstallService
{
    /**
     * @throws \Exception
     */
    public static function install(): ?string
    {
        try {
            $isTablePresent = MigrationRepo::checkIfTableExists();
            if (!$isTablePresent) {
                self::runMigrations(latestMigrationId: null);
                return self::createOwnerInvitation();
            } else {
                return null;
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());

            throw new \Exception('Setup failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws \Exception
     */
    public static function update(): void
    {
        try {
            $migrationFilesCount = count(scandir(dirname(__DIR__, 2) . '/database/query-migration'));
            $latestMigrationId = MigrationRepo::getLatestMigrationId();

            if ($latestMigrationId !== $migrationFilesCount) {
                self::runMigrations($latestMigrationId);
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());

            throw new \Exception('Setup failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws \Exception
     */
    private static function runMigrations(?int $latestMigrationId): void
    {
        $rootDir = dirname(__DIR__, 2);
        $migrationDir = "$rootDir/database/query-migration";
        $migrationFiles = array_diff(scandir($migrationDir), ['..', '.']);

        uasort($migrationFiles, static function (string $a, string $b): int {
            $aVersion = (int)preg_replace('/v(\d+)_/', '', $a);
            $bVersion = (int)preg_replace('/v(\d+)_/', '', $b);

            return $aVersion <=> $bVersion;
        });

        if ($latestMigrationId !== null) {
            $migrationFiles = array_slice($migrationFiles, $latestMigrationId);
        }

        foreach ($migrationFiles as $migrationFile) {
            $migrationSql = file_get_contents("$migrationDir/$migrationFile");
            if (!$migrationSql) {
                throw new \RuntimeException("Failed to read migration file: $migrationFile");
            }

            $isNotFirstMigration = !str_starts_with($migrationFile, 'v1_');
            $hash = Crypto::hash($migrationSql);

            try {
                if ($isNotFirstMigration && MigrationRepo::checkIfMigrationExists($hash)) {
                    throw new \RuntimeException("Migration already applied: $migrationFile");
                }

                MariaTransactor::update($migrationSql);
                MigrationRepo::insertMigration($migrationFile, $hash, Moment::now());
            } catch (\Exception $e) {
                throw new \Exception("Failed to execute migration: $migrationFile - {$e->getMessage()}", 0, $e);
            }
        }
    }

    /**
     * @throws RandomException
     * @throws \Exception
     */
    private static function createOwnerInvitation(): string
    {
        $ownerEmail = isset($_ENV['APP_OWNER_EMAIL']) && is_string($_ENV['APP_OWNER_EMAIL'])
            ? $_ENV['APP_OWNER_EMAIL']
            : throw new Anomaly('APP_OWNER_EMAIL environment variable is not set');;

        $appUrl = isset($_ENV['APP_URL']) && is_string($_ENV['APP_URL'])
            ? $_ENV['APP_URL']
            : throw new Anomaly('APP_URL environment variable is not set');;

        $invitationToken = Binary::apply(random_bytes(16));
        if (!InvitationRepo::insertInvitation($ownerEmail, $invitationToken, true, Moment::now())) {
            throw new InvitationCreationException();
        }

        $base64Token = Base64String::fromBytes($invitationToken);
        $encodedBase64Token = urlencode($base64Token->value);

        $joinPath = _('url_join');
        return "$appUrl/$joinPath?t=$encodedBase64Token";
    }
}

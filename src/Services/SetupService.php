<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\Anomaly;
use App\Core\MariaTransactor;
use App\Core\Types\Base64String;
use App\Core\Types\Binary;
use App\Domain\Repositories\InvitationRepo;
use App\Domain\Repositories\SetupRepo;
use App\Exceptions\InvitationCreationException;
use Random\RandomException;

final class SetupService
{
    /**
     * @throws \Exception
     */
    public static function runSetup(): ?string
    {
        if (SetupRepo::isReady()) {
            return null;
        }

        try {
            self::runMigrations();
            $invitationUrl = self::createOwnerInvitation();
            SetupRepo::markSetupAsDone();

            return $invitationUrl;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());

            throw new \Exception('Setup failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws \Exception
     */
    private static function runMigrations(): void
    {
        $rootDir = dirname(__DIR__, 2);
        $migrationFile = "$rootDir/database/query-migration/setup.sql";

        if (!file_exists($migrationFile)) {
            throw new \Exception('Migration file not found');
        }

        $migrationSql = file_get_contents($migrationFile);
        if (!$migrationSql) {
            throw new \Exception('Failed to read migration file');
        }

        try {
            MariaTransactor::update($migrationSql);
        } catch (\Exception $e) {
            throw new \Exception('Failed to execute migration: ' . $e->getMessage(), 0, $e);
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
        if (!InvitationRepo::insertInvitation($ownerEmail, $invitationToken, true)) {
            throw new InvitationCreationException();
        }

        $base64Token = Base64String::fromBytes($invitationToken);
        $encodedBase64Token = urlencode($base64Token->value);

        $joinPath = _('url_join');
        return "$appUrl/$joinPath?t=$encodedBase64Token";
    }
}

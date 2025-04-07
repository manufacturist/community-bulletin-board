<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Core\MariaTransactor;

final class SetupRepo
{
    public static function isReady(): bool
    {
        $query = "SELECT ready FROM setup LIMIT 1";

        try {
            $result = MariaTransactor::unique($query);
            return $result && isset($result['ready']) && $result['ready'] === 1;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function markSetupAsDone(): void
    {
        $query = "UPDATE setup SET ready = TRUE";
        MariaTransactor::update($query);
    }
}

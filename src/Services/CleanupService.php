<?php

namespace App\Services;

use App\Domain\Repositories\AuthenticationRepo;
use App\Domain\Repositories\InvitationRepo;

final class CleanupService
{
    public static function cleanup(): void
    {
        $deletedTokens = AuthenticationRepo::deleteExpiredTokens();
        $deletedInvitations = InvitationRepo::deleteExpiredInvitations();

        if (!$deletedTokens || !$deletedInvitations) error_log('Failed to cleanup.');
        else error_log('Clean-up successful.');
    }
}
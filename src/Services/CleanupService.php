<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Repositories\AuthenticationRepo;
use App\Domain\Repositories\InvitationRepo;
use App\Domain\Repositories\PostRepo;

final class CleanupService
{
    public static function cleanup(): void
    {
        try {
            $deletedTokens = AuthenticationRepo::deleteExpiredTokens();
            $deletedInvitations = InvitationRepo::deleteExpiredInvitations();

            // TODO: env APP_RESOLVED_POSTS_DELETION_DAYS
            $deletedResolvedPosts = PostRepo::deleteOlderResolvedPosts(daysSinceResolved: 5);

            if (!$deletedTokens || !$deletedInvitations || !$deletedResolvedPosts) error_log('Failed to cleanup.');
            else error_log('Clean-up successful.');
        } catch (\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
        }
    }
}
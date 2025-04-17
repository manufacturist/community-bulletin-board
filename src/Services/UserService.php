<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Exceptions\Anomaly;
use App\Core\Exceptions\Forbidden;
use App\Domain\Models\UserInfo;
use App\Domain\Repositories\UserRepo;
use App\Exceptions\UserNotFound;

final class UserService
{
    /**
     * @return UserInfo[]
     * @throws \Exception
     */
    public static function fetchAll(): array
    {
        $users = UserRepo::selectAll();

        return array_map(fn($user) => new UserInfo(
            $user->id,
            Crypto::decrypt($user->encryptedName),
            Crypto::decrypt($user->encryptedEmail),
            Crypto::decrypt($user->encryptedPhoneNumber),
            $user->maxActivePosts,
            $user->theme,
            $user->role
        ), $users);
    }

    /**
     * @throws Forbidden
     * @throws \Exception
     */
    public static function deleteUser(int $userId, UserInfo $currentUser): void
    {
        $user = UserRepo::selectUserById($userId);
        if (!$user) {
            throw new UserNotFound($userId);
        }

        $isOwner = $currentUser->isOwner();
        $isAdminDeletingUser = ($currentUser->isAdmin() && $user->role === 'member');
        $isNotCurrentUser = $currentUser->id !== $userId;
        if (!(($isOwner || $isAdminDeletingUser) && $isNotCurrentUser)) {
            throw new Forbidden('You do not have permission to delete this user.');
        }

        if (!UserRepo::deleteUserById($userId)) {
            throw new Anomaly('Failed to delete user.');
        }
    }

    /**
     * @throws Forbidden
     * @throws \Exception
     */
    public static function updateUserMaxActivePosts(int $userId, int $adjustment, UserInfo $currentUser): UserInfo
    {
        if (!$currentUser->isAdmin()) {
            throw new Forbidden('Only administrators can update user post limits.');
        }

        $user = UserRepo::selectUserById($userId);
        if (!$user) {
            throw new UserNotFound($userId);
        }

        $newMaxActivePosts = $user->maxActivePosts + $adjustment;

        $newMaxActivePosts = max(0, min(5, $newMaxActivePosts));

        if (!UserRepo::updateMaxActivePosts($userId, $newMaxActivePosts)) {
            throw new Anomaly('Failed to update user post limit.');
        }

        $updatedUser = UserRepo::selectUserById($userId);
        if (!$updatedUser) {
            throw new UserNotFound($userId);
        }

        return UserInfo::fromUser($updatedUser);
    }

    /**
     * @throws UserNotFound
     * @throws Anomaly
     */
    public static function updateUserTheme(int $userId, string $theme): UserInfo
    {
        $user = UserRepo::selectUserById($userId);
        if (!$user) {
            throw new UserNotFound($userId);
        }

        // Validate theme
        $validThemes = ['cork', 'light', 'dark'];
        if (!in_array($theme, $validThemes)) {
            throw new \InvalidArgumentException('Invalid theme. Must be one of: ' . implode(', ', $validThemes));
        }

        if (!UserRepo::updateTheme($userId, $theme)) {
            throw new Anomaly('Failed to update user theme.');
        }

        $updatedUser = UserRepo::selectUserById($userId);
        if (!$updatedUser) {
            throw new UserNotFound($userId);
        }

        return UserInfo::fromUser($updatedUser);
    }

    /**
     * @throws Forbidden
     * @throws UserNotFound
     * @throws Anomaly
     */
    public static function updateUserRole(int $userId, string $role, UserInfo $currentUser): UserInfo
    {
        if (!$currentUser->isOwner()) {
            throw new Forbidden('Only owner can update user roles.');
        }

        if ($userId == $currentUser->id) {
            throw new Forbidden('You cannot update your own user role.');
        }

        $user = UserRepo::selectUserById($userId);
        if (!$user) {
            throw new UserNotFound($userId);
        }

        if (!UserRepo::updateRole($userId, $role)) {
            throw new Anomaly('Failed to update user role.');
        }

        $user->role = $role;
        return UserInfo::fromUser($user);
    }
}

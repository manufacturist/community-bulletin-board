<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Exceptions\Anomaly;
use App\Core\Exceptions\Forbidden;
use App\Core\Types\Base64String;
use App\Domain\Models\UserInfo;
use App\Domain\Repositories\AuthenticationRepo;
use App\Domain\Repositories\UserRepo;

final class AuthService
{
    /**
     * @return null|array{Base64String, int}
     *
     * @throws \Random\RandomException
     * @throws \Exception
     */
    public static function login(string $email, string $password): ?array
    {
        $emailHash = Crypto::hash($email);
        $user = UserRepo::selectUserByEmailHash($emailHash);

        if ($user && password_verify($password, $user->passwordHash)) {
            $token = random_bytes(16);
            $tokenHash = Crypto::hash($token);

            $unixTimeStamp = $user->role == 'member'
                ? strtotime('+1 year')
                : strtotime('+2 weeks');

            $expiresAt = date('Y-m-d H:i:s.v', $unixTimeStamp);
            $isInserted = AuthenticationRepo::insertAuthentication($tokenHash, $user->id, $expiresAt);

            if (!$isInserted) {
                throw new Anomaly("Failed to store authentication token");
            }

            return [Base64String::fromBytes($token), $unixTimeStamp];
        }

        return null;
    }

    /**
     * @throws \Exception
     */
    public static function logout(Base64String $token): bool
    {
        $tokenHash = Crypto::hash($token->decode());
        return AuthenticationRepo::deleteAuthenticationByTokenHash($tokenHash);
    }

    /**
     * @throws \Exception
     */
    public static function getAuthenticatedUser(Base64String $token): UserInfo
    {
        $tokenHash = Crypto::hash($token->decode());

        $user = AuthenticationRepo::selectUserByTokenHash($tokenHash);
        if (!$user) {
            throw new Forbidden('User not authenticated');
        }

        return UserInfo::fromUser($user);
    }
}

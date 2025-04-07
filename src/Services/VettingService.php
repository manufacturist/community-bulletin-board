<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Exceptions\Anomaly;
use App\Core\Exceptions\Conflict;
use App\Core\Exceptions\Expired;
use App\Core\Exceptions\Forbidden;
use App\Core\Exceptions\NotFound;
use App\Exceptions\EmailAlreadyUsedException;
use App\Exceptions\EmailSendingException;
use App\Exceptions\InvalidEmailException;
use App\Exceptions\InvitationCreationException;
use App\Core\Types\Base64String;
use App\Core\Types\Binary;
use App\Domain\Models\Invitation;
use App\Domain\Models\UserInfo;
use App\Domain\Repositories\InvitationRepo;
use App\Domain\Repositories\UserRepo;

final class VettingService
{
    /**
     * @psalm-suppress PossiblyUnusedReturnValue
     *
     * @throws Forbidden
     * @throws InvalidEmailException
     * @throws EmailAlreadyUsedException
     * @throws EmailSendingException
     * @throws Anomaly
     * @throws \Random\RandomException
     */
    public static function createInvitation(UserInfo $user, string $email, bool $isAdmin): Base64String
    {
        if (!$user->isAdmin()) {
            throw new Forbidden('Only admins can create invitations.');
        }

        $emailPattern = "/^[^\s@]+@[^\s@]+\.[^\s@]+$/";
        if (strlen($email) <= 256 && !preg_match($emailPattern, $email)) {
            throw new InvalidEmailException();
        }

        $emailHash = Crypto::hash($email);
        $duplicateUser = UserRepo::selectUserByEmailHash($emailHash);
        $duplicateInvitation = InvitationRepo::selectInvitationByEmail($email);
        if ($duplicateUser || $duplicateInvitation) {
            throw new EmailAlreadyUsedException();
        }

        $invitationToken = Binary::apply(random_bytes(16));
        $base64Token = Base64String::fromBytes($invitationToken);

        try {
            if (!InvitationRepo::insertInvitation($email, $invitationToken, $isAdmin)) {
                throw new InvitationCreationException();
            }

            try {
                EmailService::sendInvitationEmail($email, $base64Token, $isAdmin);
            } catch (\Exception $e) {
                $deletedInvitation = InvitationRepo::deleteInvitationByEmail($email);
                $errorMessage = "Failed to send invitation email (deleted? $deletedInvitation): " . $e->getMessage();

                throw new EmailSendingException($errorMessage);
            }

            return $base64Token;
        } catch (EmailSendingException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Anomaly($e->getMessage());
        }
    }

    /**
     * @throws Expired
     * @throws Conflict
     * @throws NotFound
     * @throws Anomaly
     * @throws \DateMalformedStringException
     */
    public static function acceptInvitation(
        Base64String $invitationToken,
        string       $name,
        string       $phoneNumber,
        string       $password
    ): void
    {
        $token = Binary::apply($invitationToken->decode());

        $invitation = InvitationRepo::selectInvitationByToken($token);
        if (!$invitation) {
            throw new NotFound('Invitation not found.');
        }

        if ($invitation->createdAt->modify('+ 1 day') < \DateTime::createFromTimestamp(strtotime("now"))) {
            $isDeleted = InvitationRepo::deleteInvitationByToken($token);

            // TODO: Maybe 'Either' approach https://github.com/widmogrod/php-functional to void such throws
            if ($isDeleted) {
                throw new Expired('Expired invitation.');
            } else {
                throw new Anomaly('Invitation is expired, but the deletion failed.');
            }
        }

        if (UserRepo::selectUserByEmailHash(Crypto::hash($invitation->email))) {
            throw new EmailAlreadyUsedException();
        }

        $numberOfUsers = UserRepo::selectCount();

        $encryptedName = Crypto::encrypt($name);
        $encryptedEmail = Crypto::encrypt($invitation->email);
        $encryptedPhoneNumber = Crypto::encrypt($phoneNumber);
        $emailHash = Crypto::hash($invitation->email);
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $role = $numberOfUsers === 0 ? 'owner' : ($invitation->isAdmin ? 'admin' : 'member');

        $maxActivePosts = isset($_ENV['APP_MAX_ACTIVE_POSTS_DEFAULT']) && is_numeric($_ENV['APP_MAX_ACTIVE_POSTS_DEFAULT'])
            ? (int)$_ENV['APP_MAX_ACTIVE_POSTS_DEFAULT']
            : 2;

        $hasInsertFailed = !UserRepo::insertUser(
            encryptedName: $encryptedName,
            encryptedEmail: $encryptedEmail,
            encryptedPhoneNumber: $encryptedPhoneNumber,
            emailHash: $emailHash,
            passwordHash: $passwordHash,
            maxActivePosts: $maxActivePosts,
            role: $role,
        );

        if ($hasInsertFailed) {
            throw new Anomaly('Failed to create user.');
        }

        if (!InvitationRepo::deleteInvitationByToken($token)) {
            throw new Anomaly('Failed to delete invitation.');
        }
    }

    /**
     * @throws Anomaly
     */
    public static function declineInvitation(Base64String $invitationToken): void
    {
        if (!InvitationRepo::deleteInvitationByToken(Binary::apply($invitationToken->decode()))) {
            throw new Anomaly('Failed to delete invitation.');
        }
    }

    /**
     * @return Invitation[]
     */
    public static function getInvitations(): array
    {
        return InvitationRepo::selectAll();
    }
}

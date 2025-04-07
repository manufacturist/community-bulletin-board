<?php

declare(strict_types=1);

namespace App\Tests\Suites\Services;

use App\Core\Crypto;
use App\Core\MariaTransactor;
use App\Core\Types\Base64String;
use App\Core\Types\Binary;
use App\Domain\Repositories\InvitationRepo;
use App\Domain\Repositories\UserRepo;
use App\Services\AuthService;
use App\Services\VettingService;
use App\Tests\TestKit\TestCases\BaseTestCase;

class VettingServiceTest extends BaseTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        MariaTransactor::update("SET FOREIGN_KEY_CHECKS = 0");
        MariaTransactor::update("TRUNCATE TABLE authentications");
        MariaTransactor::update("TRUNCATE TABLE users");
        MariaTransactor::update("SET FOREIGN_KEY_CHECKS = 1");
    }

    public function testCreateUserInvitation()
    {
        // Arrange
        $admin = self::addAuthenticatedAdmin()[0];

        // Act
        $email = self::$faker->email();
        $invitationToken = VettingService::createInvitation($admin, $email, false);

        // Assert
        $invitation = MariaTransactor::unique(
            "SELECT * FROM invitations WHERE token = :token",
            [":token" => $invitationToken->decode()]
        );

        $this->assertNotEmpty($invitation);
        $this->assertEquals($email, $invitation['email']);
    }

    public function testSameEmailUsage()
    {
        // Arrange
        $admin = self::addAuthenticatedAdmin()[0];

        $email = self::$faker->email();
        VettingService::createInvitation($admin, $email, false);

        // Act
        try {
            VettingService::createInvitation($admin, $email, false);
        } catch (\Exception $e) {
            // Assert
            self::assertEquals("Email already used by another user or invitation.", $e->getMessage());
        }
    }

    public function testFirstInvitationAcceptanceYieldsOwnerUser()
    {
        // Arrange
        $admin = self::addAuthenticatedAdmin()[0];

        // Act
        $email = self::$faker->email();
        $invitationToken = VettingService::createInvitation($admin, $email, false);

        $name = self::$faker->firstName();
        $phoneNumber = self::$faker->phoneNumber();
        VettingService::acceptInvitation($invitationToken, $name, $phoneNumber, self::$faker->password());

        // Assert
        $emailHash = Crypto::hash($email);
        $user = UserRepo::selectUserByEmailHash($emailHash);

        $this->assertNotEmpty($user);
        $this->assertEquals($name, Crypto::decrypt($user->encryptedName));
        $this->assertEquals($email, Crypto::decrypt($user->encryptedEmail));
        $this->assertEquals($phoneNumber, Crypto::decrypt($user->encryptedPhoneNumber));
        $this->assertEquals('member', $user->role);

        $invitation = MariaTransactor::unique(
            "SELECT * FROM invitations WHERE token = :token",
            [":token" => $invitationToken->decode()]
        );

        $this->assertEmpty($invitation);
    }

    public function testRefuseInvitation()
    {
        // Arrange
        $admin = self::addAuthenticatedAdmin()[0];

        // Act
        $invitationToken = VettingService::createInvitation($admin, self::$faker->email(), false);
        VettingService::declineInvitation($invitationToken);

        // Assert
        $invitation = MariaTransactor::unique(
            "SELECT * FROM invitations WHERE token = :token",
            [":token" => $invitationToken->decode()]
        );

        $this->assertEmpty($invitation);
    }

    public function testInvitedAdminCanCreateInvitation()
    {
        // Arrange
        $admin = self::addAuthenticatedAdmin()[0];

        $email = self::$faker->email();
        $password = self::$faker->password(8);

        $invitationToken = VettingService::createInvitation($admin, $email, true);

        VettingService::acceptInvitation(
            invitationToken: $invitationToken,
            name: self::$faker->firstName(),
            phoneNumber: self::$faker->phoneNumber(),
            password: $password
        );

        $newAdminToken = AuthService::login($email, $password)[0];
        $newAdmin = AuthService::getAuthenticatedUser($newAdminToken);

        // Act
        VettingService::createInvitation($newAdmin, self::$faker->email(), true);

        // Assert
        $this->expectNotToPerformAssertions();
    }

    public function testExpiredInvitation()
    {
        // Arrange
        $invitationToken = Binary::apply(random_bytes(16));

        $query = "
            INSERT INTO invitations (email, token, is_admin, created_at) 
            VALUES (:email, :token, :is_admin, :created_at)
        ";

        $params = [
            ':email' => self::$faker->email(),
            ':token' => $invitationToken->value,
            ':is_admin' => 0,
            ':created_at' => date("Y-m-d H:i:s", strtotime("-2 days"))
        ];

        // Act
        try {
            MariaTransactor::update($query, $params);

            VettingService::acceptInvitation(
                invitationToken: Base64String::fromBytes($invitationToken),
                name: "foo",
                phoneNumber: "12345678",
                password: "12345678"
            );
        } catch (\Exception $e) {
            // Assert
            $foundInvitation = InvitationRepo::selectInvitationByToken($invitationToken);

            self::assertEquals("Expired invitation.", $e->getMessage());
            self::assertNull($foundInvitation);
        }
    }
}

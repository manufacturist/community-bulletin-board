<?php

declare(strict_types=1);

namespace App\Tests\Suites\WebApp;

use App\Controllers\RequestDTOs\AcceptInvitationDTO;
use App\Controllers\RequestDTOs\DeclineInvitationDTO;
use App\Controllers\RequestDTOs\LoginDTO;
use App\Controllers\RequestDTOs\NewPostDTO;
use App\Core\Crypto;
use App\Core\MariaTransactor;
use App\Core\Types\Base64String;
use App\Core\Types\Binary;
use App\Core\Types\Moment;
use App\Domain\Repositories\InvitationRepo;
use App\Tests\TestKit\WebApp\WebAppAPI;
use App\Tests\TestKit\WebApp\WebAppPages;
use App\Tests\TestKit\TestCases\WebAppTestCase;
use GuzzleHttp\Cookie\SetCookie;
use Psr\Http\Message\ResponseInterface;

final class UserTest extends WebAppTestCase
{
    private static ?string $baseUrl = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$baseUrl = "http://127.0.0.1:" . self::$webAppPort;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        MariaTransactor::update("TRUNCATE TABLE posts");
    }

    public function testAcceptInvitation(): void
    {
        // Arrange
        $api = new WebAppAPI(self::$baseUrl);

        $email = self::$faker->email();
        $password = self::$faker->password(8);

        $adminInvitationToken = Binary::apply(random_bytes(16));
        InvitationRepo::insertInvitation($email, $adminInvitationToken, true, Moment::now());

        // Act
        $acceptInvitation = new AcceptInvitationDTO(
            token: base64_encode($adminInvitationToken->value),
            name: self::$faker->firstName(),
            phoneNumber: self::$faker->phoneNumber(),
            password: $password
        );

        $invitationResponse = $api->acceptInvitation($acceptInvitation);

        // Assert
        self::assertEquals(204, $invitationResponse->getStatusCode(), $invitationResponse->getBody()->getContents());

        $login = new LoginDTO($email, $password);
        $loginResponse = $api->login($login);
        self::assertEquals(204, $loginResponse->getStatusCode(), $invitationResponse->getBody()->getContents());

        $authToken = $this->getAuthCookie($loginResponse);
        self::assertNotNull($authToken);
        self::assertEquals($authToken->value, base64_encode($authToken->decode()));
    }

    public function testDeclineInvitation(): void
    {
        // Arrange
        $api = new WebAppAPI(self::$baseUrl);

        $email = self::$faker->email();
        $password = self::$faker->password(8);

        $adminInvitationToken = Binary::apply(random_bytes(16));
        InvitationRepo::insertInvitation($email, $adminInvitationToken, true, Moment::now());

        $declineInvitation = new DeclineInvitationDTO(base64_encode($adminInvitationToken->value));

        // Act
        $invitationResponse = $api->declineInvitation($declineInvitation);

        // Assert
        self::assertEquals(204, $invitationResponse->getStatusCode(), $invitationResponse->getBody()->getContents());

        $login = new LoginDTO($email, $password);
        $loginResponse = $api->login($login);
        self::assertEquals(401, $loginResponse->getStatusCode(), $invitationResponse->getBody()->getContents());

        $authToken = $this->getAuthCookie($loginResponse);
        self::assertNull($authToken);
    }

    public function testNewAdminHomePage(): void
    {
        // Arrange
        $api = new WebAppAPI(self::$baseUrl);
        $pages = new WebAppPages(self::$baseUrl, $api->client);

        [$user, $password] = $api->createUser(isAdmin: true);
        $api->loginChecked(new LoginDTO(Crypto::decrypt($user->encryptedEmail), $password));

        // Act
        $homePage = $pages->getHomePage();

        // Assert
        $html = $homePage->getBody()->getContents();
        self::assertStringContainsString("Admin", $html);
        self::assertStringNotContainsString(Crypto::decrypt($user->encryptedName), $html);
    }

    public function testNewUserHomePage(): void
    {
        // Arrange
        $api = new WebAppAPI(self::$baseUrl);
        $pages = new WebAppPages(self::$baseUrl, $api->client);

        [$user, $password] = $api->createUser(isAdmin: false);
        $api->loginChecked(new LoginDTO(Crypto::decrypt($user->encryptedEmail), $password));

        // Act
        $homePage = $pages->getHomePage();

        // Assert
        $html = $homePage->getBody()->getContents();
        self::assertStringNotContainsString("Admin", $html);
        self::assertStringContainsString(Crypto::decrypt($user->encryptedName), $html);
    }

    public function testUserCreatesPost(): void
    {
        // Arrange
        $api = new WebAppAPI(self::$baseUrl);
        $pages = new WebAppPages(self::$baseUrl, $api->client);

        $api->createAuthenticatedUser(isAdmin: false);

        $description = "First post!";
        $pinColor = "green";
        $link = "http://foo.bar";
        $expiresAt = self::$faker->nearingDate();

        // Act
        $newPost = new NewPostDTO($description, $pinColor, $link, $expiresAt);
        $postInfo = $api->createPostChecked($newPost);

        // Assert
        self::assertEquals($description, $postInfo->description);
        self::assertEquals($pinColor, $postInfo->pinColor);
        self::assertEquals($link, $postInfo->link);

        $homePage = $pages->getHomePage();
        $html = $homePage->getBody()->getContents();
        self::assertStringContainsString("pin-" . $pinColor, $html);
        self::assertStringContainsString($description, $html);
        self::assertStringContainsString($postInfo->phoneNumber, $html);
        self::assertStringContainsString($postInfo->userName, $html);
        self::assertStringContainsString($link, $html);
    }

    public function testUserDeletesPost(): void
    {
        // Arrange
        $api = new WebAppAPI(self::$baseUrl);
        $pages = new WebAppPages(self::$baseUrl, $api->client);

        $api->createAuthenticatedUser(isAdmin: false);

        $newPost = new NewPostDTO(self::$faker->text(), "pink", null, self::$faker->nearingDate());
        $postInfo = $api->createPostChecked($newPost);

        // Act
        $api->deletePostChecked($postInfo->id);

        // Assert
        $homePage = $pages->getHomePage();
        $html = $homePage->getBody()->getContents();
        self::assertStringNotContainsString("pin-pink", $html);
        self::assertStringNotContainsString($postInfo->description, $html);
        self::assertStringNotContainsString($postInfo->phoneNumber, $html);
    }

    public function testUserCantViewAdminPage(): void
    {
        // Arrange
        $api = new WebAppAPI(self::$baseUrl);
        $pages = new WebAppPages(self::$baseUrl, $api->client);

        $api->createAuthenticatedUser(isAdmin: false);

        // Act
        $adminPage = $pages->getAdminPage();

        // Assert
        self::assertEquals(404, $adminPage->getStatusCode());
    }

    private function getAuthCookie(ResponseInterface $response): ?Base64String
    {
        $authToken = null;

        foreach ($response->getHeader('Set-Cookie') as $cookie) {
            $setCookie = SetCookie::fromString($cookie);
            if ($setCookie->getName() === "auth_token") {
                $authToken = urldecode($setCookie->getValue());
                break;
            }
        }

        return $authToken ? Base64String::apply($authToken) : null;
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Suites\WebApp;

use App\Controllers\RequestDTOs\NewPostDTO;
use App\Tests\TestKit\WebApp\WebAppAPI;
use App\Tests\TestKit\WebApp\WebAppPages;
use App\Tests\TestKit\TestCases\WebAppTestCase;

final class PagesTest extends WebAppTestCase
{
    private static ?string $baseUrl = null;
    private static ?WebAppAPI $api = null;
    private static ?WebAppPages $pages = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$baseUrl = "http://127.0.0.1:" . self::$webAppPort;
        self::$api = new WebAppAPI(self::$baseUrl);
        self::$pages = new WebAppPages(self::$baseUrl);
    }

    public function testHomePage(): void
    {
        $response = self::$pages->getHomePage();
        $document = $response->getBody()->getContents();

        self::assertStringContainsString("Login", $document, $document);
    }

    public function testJoinPageWithoutToken(): void
    {
        $response = self::$pages->getJoinPage();
        self::assertEquals(404, $response->getStatusCode());
    }

    public function testJoinPageWithNonExistingToken(): void
    {
        $response = self::$pages->getJoinPage();
        self::assertEquals(404, $response->getStatusCode());
    }

    public function testAdminPage(): void
    {
        $response = self::$pages->getAdminPage();
        $document = $response->getBody()->getContents();

        self::assertEquals(404, $response->getStatusCode(), $document);
    }

    public function testPublicPage(): void
    {
        // Arrange
        $authenticatedPages = new WebAppPages(self::$baseUrl, self::$api->client);
        self::$api->createAuthenticatedUser(isAdmin: false);

        $unauthenticatedPages = self::$pages;

        $description = "First post!";
        $pinColor = "green";
        $link = "http://foo.bar";
        $expiresAt = self::$faker->nearingDate();

        // Act
        $newPost = new NewPostDTO($description, $pinColor, $link, $expiresAt);
        $postInfo = self::$api->createPostChecked($newPost);

        // Assert
        $authenticatedUserPageResponse = $authenticatedPages->getPublicPage();
        $unauthenticatedUserPageResponse = $unauthenticatedPages->getPublicPage();
        self::assertEquals(302, $authenticatedUserPageResponse->getStatusCode());
        self::assertEquals(200, $unauthenticatedUserPageResponse->getStatusCode());

        $pageContents = $unauthenticatedUserPageResponse->getBody()->getContents();
        self::assertStringContainsString($description, $pageContents, $pageContents);
        self::assertStringContainsString($pinColor, $pageContents, $pageContents);
        self::assertStringNotContainsString($postInfo->userName, $pageContents, $pageContents);
        self::assertStringNotContainsString($postInfo->phoneNumber, $pageContents, $pageContents);
        self::assertStringNotContainsString($link, $pageContents, $pageContents);
    }
}

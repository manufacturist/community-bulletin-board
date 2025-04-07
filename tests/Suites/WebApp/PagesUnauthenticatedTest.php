<?php

declare(strict_types=1);

namespace App\Tests\Suites\WebApp;

use App\Tests\TestKit\WebApp\WebAppPages;
use App\Tests\TestKit\TestCases\WebAppTestCase;

final class PagesUnauthenticatedTest extends WebAppTestCase
{
    private static ?WebAppPages $pages = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$pages = new WebAppPages("http://127.0.0.1:" . self::$webAppPort);
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
        $document = $response->getBody()->getContents();

        self::assertEquals(404, $response->getStatusCode());
        self::assertStringContainsString("", $document, $document);
    }

    public function testJoinPageWithNonExistingToken(): void
    {
        $response = self::$pages->getJoinPage();
        $document = $response->getBody()->getContents();

        self::assertEquals(404, $response->getStatusCode());
        self::assertStringContainsString("", $document, $document);
    }

    public function testAdminPage(): void
    {
        $response = self::$pages->getAdminPage();
        $document = $response->getBody()->getContents();

        self::assertEquals(404, $response->getStatusCode(), $document);
    }
}

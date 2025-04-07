<?php

declare(strict_types=1);

namespace App\Tests\Suites\Services;

use App\Core\MariaTransactor;
use App\Services\AuthService;
use App\Tests\TestKit\TestCases\BaseTestCase;

class AuthServiceTest extends BaseTestCase
{
    public function testLogout()
    {
        // Arrange
        [$user, $token] = self::addAuthenticatedUser();

        // Act
        $isLoggedOut = AuthService::logout($token);

        // Assert
        $this->assertTrue($isLoggedOut);

        $query = "SELECT token_hash FROM authentications WHERE user_id = :user_id";
        $params = [':user_id' => $user->id];

        $result = MariaTransactor::query($query, $params);
        $this->assertEmpty($result);
    }
}

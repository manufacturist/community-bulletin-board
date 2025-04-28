<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\RequestDTOs\AcceptInvitationDTO;
use App\Controllers\RequestDTOs\DeclineInvitationDTO;
use App\Controllers\RequestDTOs\LoginDTO;
use App\Controllers\RequestDTOs\UpdateThemeDTO;
use App\Core\Exceptions\Forbidden;
use App\Core\Exceptions\InvalidState;
use App\Core\Exceptions\Unauthorized;
use App\Core\JSON;
use App\Core\Types\Base64String;
use App\Core\Types\SystemTheme;
use App\Exceptions\UserNotFound;
use App\Services\AuthService;
use App\Services\UserService;
use App\Services\VettingService;
use Psr\Container\ContainerInterface;
use Slim\Http\Response as Response;
use Slim\Http\ServerRequest as Request;

final class UserController
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @throws \Exception
     */
    public function acceptInvitation(Request $request, Response $response): Response
    {
        $body = JSON::deserialize($request->getBody()->getContents(), AcceptInvitationDTO::class);

        if (strlen($body->name) > 35) {
            throw new InvalidState('At most 35 characters for first name.');
        }

        if (!preg_match('/^\+?([0-9 .\-()]{1,20})$/', $body->phoneNumber)) {
            throw new InvalidState('Invalid phone number format.');
        }

        if (strlen($body->password) < 8) {
            throw new InvalidState('Password must be at least 8 characters long.');
        }

        VettingService::acceptInvitation(
            invitationToken: Base64String::apply($body->token),
            name: $body->name,
            phoneNumber: $body->phoneNumber,
            password: $body->password
        );

        return $response->withStatus(204);
    }

    /**
     * @throws \Exception
     */
    public function declineInvitation(Request $request, Response $response): Response
    {
        $body = JSON::deserialize($request->getBody()->getContents(), DeclineInvitationDTO::class);

        VettingService::declineInvitation(Base64String::apply($body->inviteToken));

        return $response->withStatus(204);
    }

    /**
     * @throws \Exception
     */
    public function login(Request $request, Response $response): Response
    {
        $body = JSON::deserialize($request->getBody()->getContents(), LoginDTO::class);

        $tokenAndExpiresTimestamp = AuthService::login($body->email, $body->password);
        if (is_null($tokenAndExpiresTimestamp)) {
            throw new Unauthorized('Invalid credentials.');
        }

        [
            /** @var Base64String $token */
            $token,
            /** @var int $expirationTimestamp */
            $expirationTimestamp
        ] = $tokenAndExpiresTimestamp;

        $expires = gmdate('D, d M Y H:i:s T', $expirationTimestamp);
        $authTokenValue = urlencode($token->value);

        $secure = $_ENV['APP_ENV'] == 'production' ? 'Secure;' : '';

        return $response
            ->withHeader('Set-Cookie', "auth_token=$authTokenValue; HttpOnly; $secure SameSite=Strict; Path=/; Expires=" . $expires)
            ->withStatus(204);
    }

    /**
     * @throws \Exception
     */
    public function logout(Request $request, Response $response): Response
    {
        $clearAuthCookie = 'auth_token=; HttpOnly; Secure; SameSite=Strict; Expires=Thu, 01 Jan 1970 00:00:00 GMT';

        $decoratedRequest = SlimRequestDecorator::decorate($request);
        if (!AuthService::logout($decoratedRequest->getAuth())) {
            return $response
                ->withHeader('Set-Cookie', $clearAuthCookie)
                ->withStatus(401);
        }

        return $response
            ->withHeader('Set-Cookie', $clearAuthCookie)
            ->withStatus(204);
    }

    /**
     * @param array<string, mixed> $args
     * @throws \Exception
     */
    public function updateUserTheme(Request $request, Response $response, array $args): Response
    {
        $userId = isset($args['userId']) && is_numeric($args['userId'])
            ? (int)$args['userId']
            : throw new InvalidState('Not a valid user id.');

        $updateTheme = JSON::deserialize($request->getBody()->getContents(), UpdateThemeDTO::class);

        $decoratedRequest = SlimRequestDecorator::decorate($request);
        $authenticatedUser = $decoratedRequest->getUser();

        if ($userId !== $authenticatedUser->id) {
            return $response->withStatus(403)->withJson(['error' => 'You can only update your own theme']);
        }

        $enabledThemes = is_string($_ENV['APP_ENABLED_THEMES'] ?? null)
            ? explode(',', $_ENV['APP_ENABLED_THEMES'])
            : SystemTheme::THEMES;

        if (!in_array($updateTheme->theme, $enabledThemes)) {
            return $response->withStatus(400)->withJson([
                'error' => 'Invalid theme. Must be one of: ' . implode(', ', $enabledThemes)
            ]);
        }

        $updatedUser = UserService::updateUserTheme($userId, $updateTheme->theme);

        return $response->withStatus(200)->withJson($updatedUser);
    }

    /**
     * @param array<string, mixed> $args
     * @throws \Exception
     */
    public function deleteUser(Request $request, Response $response, array $args): Response
    {
        $decoratedRequest = SlimRequestDecorator::decorate($request);
        $currentUser = $decoratedRequest->getUser();

        if (!isset($args['userId']) || !is_numeric($args['userId'])) {
            return $response->withJson(['error' => 'User id is missing or not a number.'], 400);
        }

        $userId = (int)$args['userId'];
        $isSelfDelete = $userId === $currentUser->id && !$currentUser->isAdmin();

        try {
            UserService::deleteUser($userId, $currentUser);

            if ($isSelfDelete) {
                $clearAuthCookie = 'auth_token=; HttpOnly; Secure; SameSite=Strict; Expires=Thu, 01 Jan 1970 00:00:00 GMT';
                return $response
                    ->withHeader('Set-Cookie', $clearAuthCookie)
                    ->withStatus(204);
            }

            return $response->withStatus(204);
        } catch (Forbidden $e) {
            return $response->withJson(['error' => $e->getMessage()], 403);
        } catch (UserNotFound $e) {
            return $response->withJson(['error' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            return $response->withJson(['error' => 'An unexpected error occurred.'], 500);
        }
    }
}

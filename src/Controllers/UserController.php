<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\RequestDTOs\AcceptInvitationDTO;
use App\Controllers\RequestDTOs\DeclineInvitationDTO;
use App\Controllers\RequestDTOs\LoginDTO;
use App\Core\Exceptions\InvalidState;
use App\Core\Exceptions\Unauthorized;
use App\Core\JSON;
use App\Core\Types\Base64String;
use App\Services\AuthService;
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

        return $response
            ->withHeader('Set-Cookie', "auth_token=$authTokenValue; HttpOnly; SameSite=Strict; Path=/; Expires=" . $expires)
            ->withStatus(204);
    }

    /**
     * @throws \Exception
     */
    public function logout(Request $request, Response $response): Response
    {
        $decoratedRequest = SlimRequestDecorator::decorate($request);

        $clearAuthCookie = 'auth_token=; HttpOnly; Secure; SameSite=Strict; Expires=Thu, 01 Jan 1970 00:00:00 GMT';

        if (!AuthService::logout($decoratedRequest->getAuth())) {
            return $response
                ->withHeader('Set-Cookie', $clearAuthCookie)
                ->withStatus(401);
        }

        return $response
            ->withHeader('Set-Cookie', $clearAuthCookie)
            ->withStatus(204);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\RequestDTOs\NewInvitationDTO;
use App\Controllers\RequestDTOs\UpdateMaxPostsDTO;
use App\Controllers\RequestDTOs\UpdateRoleDTO;
use App\Exceptions\EmailAlreadyUsedException;
use App\Exceptions\EmailSendingException;
use App\Core\Exceptions\Forbidden;
use App\Exceptions\InvalidEmailException;
use App\Exceptions\InvitationCreationException;
use App\Core\JSON;
use App\Services\UserService;
use App\Services\VettingService;
use Psr\Container\ContainerInterface;
use Slim\Http\Response as Response;
use Slim\Http\ServerRequest as Request;

final class AdminController
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @throws InvalidEmailException
     * @throws Forbidden
     * @throws EmailSendingException
     * @throws EmailAlreadyUsedException
     * @throws InvitationCreationException
     */
    public function invite(Request $request, Response $response): Response
    {
        $decoratedRequest = SlimRequestDecorator::decorate($request);
        $user = $decoratedRequest->getUser();

        $body = JSON::deserialize($decoratedRequest->getBody()->getContents(), NewInvitationDTO::class);

        VettingService::createInvitation($user, $body->email, $body->isAdmin);

        return $response->withStatus(204);
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

        UserService::deleteUser(
            userId: (int)$args['userId'],
            currentUser: $currentUser
        );

        return $response->withStatus(204);
    }

    /**
     * @param array<string, mixed> $args
     * @throws \Exception
     */
    public function updateUserMaxPosts(Request $request, Response $response, array $args): Response
    {
        $decoratedRequest = SlimRequestDecorator::decorate($request);
        $currentUser = $decoratedRequest->getUser();

        if (!isset($args['userId']) || !is_numeric($args['userId'])) {
            return $response->withJson(['error' => 'User id is missing or not a number.'], 400);
        }

        $body = JSON::deserialize($decoratedRequest->getBody()->getContents(), UpdateMaxPostsDTO::class);

        if ($body->adjustment !== 1 && $body->adjustment !== -1) {
            return $response->withJson(['error' => 'Adjustment value must be either 1 or -1.'], 400);
        }

        $updatedUser = UserService::updateUserMaxActivePosts(
            userId: (int)$args['userId'],
            adjustment: $body->adjustment,
            currentUser: $currentUser
        );

        return $response->withJson($updatedUser, 200);
    }


    /**
     * @param array<string, mixed> $args
     * @throws \Exception
     */
    public function updateUserRole(Request $request, Response $response, array $args): Response
    {
        $decoratedRequest = SlimRequestDecorator::decorate($request);
        $currentUser = $decoratedRequest->getUser();

        if (!isset($args['userId']) || !is_numeric($args['userId'])) {
            return $response->withJson(['error' => 'User id is missing or not a number.'], 400);
        }

        $body = JSON::deserialize($decoratedRequest->getBody()->getContents(), UpdateRoleDTO::class);

        if (!in_array($body->role, ['member', 'admin'])) {
            return $response->withJson(['error' => 'Role value must be either "member" or "admin".'], 400);
        }

        $updatedUser = UserService::updateUserRole(
            userId: (int)$args['userId'],
            role: $body->role,
            currentUser: $currentUser
        );

        return $response->withJson($updatedUser, 200);
    }
}

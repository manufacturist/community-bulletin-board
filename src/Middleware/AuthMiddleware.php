<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Controllers\SlimRequestDecorator;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Http\ServerRequest as Request;

final class AuthMiddleware
{
    /**
     * @throws \Exception
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $decoratedRequest = new SlimRequestDecorator($request);

        try {
            $user = AuthService::getAuthenticatedUser($decoratedRequest->getAuth());
            $decoratedRequest = $decoratedRequest->withAttribute('user', $user);
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        return $handler->handle($decoratedRequest);
    }
}

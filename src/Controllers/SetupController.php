<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Exceptions\Unauthorized;
use App\Services\InstallService;
use Slim\Http\Response as Response;
use Slim\Http\ServerRequest as Request;

final class SetupController
{
    public function install(Request $_, Response $response): Response
    {
        try {
            $invitationUrl = InstallService::install();
            if ($invitationUrl) {
                return $response->withStatus(302)->withHeader('Location', $invitationUrl);
            } else {
                return $response->withJson(['error' => 'Setup failed. Check logs to see why'], 500);
            }
        } catch (\Exception $e) {
            return $response->withJson(['error' => 'Setup failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @throws Unauthorized
     */
    public function update(Request $request, Response $response): Response
    {
        $decoratedRequest = SlimRequestDecorator::decorate($request);
        $user = $decoratedRequest->getUser();

        if ($user->role !== 'owner') {
            throw new \RuntimeException("Only owner can perform updates.");
        }

        InstallService::update();

        return $response->withRedirect('/');
    }
}

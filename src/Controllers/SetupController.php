<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SetupService;
use Slim\Http\Response as Response;
use Slim\Http\ServerRequest as Request;

final class SetupController
{
    public function setup(Request $_, Response $response): Response
    {
        try {
            $invitationUrl = SetupService::runSetup();
            if ($invitationUrl) {
                return $response->withStatus(302)->withHeader('Location', $invitationUrl);
            } else {
                return $response->withJson(['error' => 'Setup failed. Check logs to see why'], 500);
            }
        } catch (\Exception $e) {
            return $response->withJson(['error' => 'Setup failed: ' . $e->getMessage()], 500);
        }
    }
}

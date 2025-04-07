<?php

namespace App\Controllers;

use App\Core\Exceptions\Conflict;
use App\Core\Exceptions\Expired;
use App\Core\Exceptions\Forbidden;
use App\Core\Exceptions\InvalidState;
use App\Core\Exceptions\NotFound;
use App\Core\Exceptions\Unauthorized;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpException;
use Slim\Handlers\ErrorHandler;
use Slim\Psr7\Stream;

final class HttpErrorHandler extends ErrorHandler
{
    #[\Override]
    protected function respond(): ResponseInterface
    {
        $exception = $this->exception;

        if ($exception instanceof Unauthorized) {
            $statusCode = 401;
            $type = 'UNAUTHORIZED';
            $description = $exception->getMessage();
        } elseif ($exception instanceof Forbidden) {
            $statusCode = 403;
            $type = 'FORBIDDEN';
            $description = $exception->getMessage();
        } elseif ($exception instanceof NotFound) {
            $statusCode = 404;
            $type = 'NOT_FOUND';
            $description = $exception->getMessage();
        } elseif ($exception instanceof InvalidState) {
            $statusCode = 400;
            $type = 'BAD_REQUEST';
            $description = $exception->getMessage();
        } elseif ($exception instanceof Conflict) {
            $statusCode = 409;
            $type = 'CONFLICT';
            $description = $exception->getMessage();
        } elseif ($exception instanceof Expired) {
            $statusCode = 410;
            $type = 'GONE';
            $description = $exception->getMessage();
        } elseif ($exception instanceof HttpException) {
            return $this->responseFactory->createResponse($exception->getCode());
        } else {
            $statusCode = 500;
            $type = 'INTERNAL_SERVER_ERROR';
            $description = $exception->getMessage();
        }

        $error = [
            'statusCode' => $statusCode,
            'type' => $type,
            'error' => $description
        ];

        $response = $this->responseFactory->createResponse($statusCode);
        $json = json_encode($error);

        $resource = fopen('php://memory', 'r+');
        if($resource && $json) {
            fwrite($resource, $json);
            rewind($resource);

            return $response->withBody(new Stream($resource))->withHeader('Content-Type', 'application/json');
        }

        return $response;
    }
}

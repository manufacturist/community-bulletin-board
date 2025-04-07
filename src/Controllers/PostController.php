<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\RequestDTOs\NewPostDTO;
use App\Core\JSON;
use App\Services\PostService;
use Psr\Container\ContainerInterface;
use Slim\Http\Response as Response;
use Slim\Http\ServerRequest as Request;

final class PostController
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @throws \Exception
     */
    public function createPost(Request $request, Response $response): Response
    {
        $decoratedRequest = SlimRequestDecorator::decorate($request);

        $user = $decoratedRequest->getUser();
        $body = JSON::deserialize($decoratedRequest->getBody()->getContents(), NewPostDTO::class);

        $unixTimeStamp = strtotime($body->expiresAt);
        if ($unixTimeStamp === false) {
            throw new \Exception("Failed to decode expiresAt.");
        }

        $expiresAt = date('Y-m-d H:i:s', $unixTimeStamp);

        $createdPost = PostService::createPost($user, $body->description, $body->pinColor, $body->link, $expiresAt);
        return $response->withJson($createdPost, 201);
    }

    /**
     * @param array<string, mixed> $args
     * @throws \Exception
     */
    public function deletePost(Request $request, Response $response, array $args): Response
    {
        $decoratedRequest = SlimRequestDecorator::decorate($request);
        $user = $decoratedRequest->getUser();

        if (!is_numeric($args['postId'])) {
            return $response->withJson(['error' => 'Post id is missing or not a number.'], 400);
        }

        $postId = (int)$args['postId'];
        PostService::deletePost($user, $postId);

        return $response->withStatus(204);
    }
}

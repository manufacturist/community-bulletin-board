<?php

namespace App\Controllers;

use App\Core\Exceptions\Unauthorized;
use App\Core\Types\Base64String;
use App\Domain\Models\UserInfo;
use Slim\Http\ServerRequest as SlimServerRequest;

final class SlimRequestDecorator extends SlimServerRequest
{
    public static function decorate(SlimServerRequest $request): SlimRequestDecorator
    {
        return new self($request);
    }

    /**
     * @throws Unauthorized
     */
    public function getUser(): UserInfo
    {
        /** @var UserInfo|mixed $user */
        $user = $this->serverRequest->getAttribute('user');

        if (!($user instanceof UserInfo)) {
            throw new Unauthorized("User not in request.");
        }

        return $user;
    }

    /**
     * @throws Unauthorized
     */
    public function getAuth(): Base64String
    {
        $cookies = $this->serverRequest->getCookieParams();

        if (!isset($cookies['auth_token']) || !is_string($cookies['auth_token'])) {
            throw new Unauthorized("Auth token is missing or invalid.");
        }

        return Base64String::apply($cookies['auth_token']);
    }

}
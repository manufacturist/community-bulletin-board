<?php

declare(strict_types=1);

namespace App\Tests\TestKit\WebApp;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class WebAppPages
{
    private Client $client;
    private string $baseUrl;

    public function __construct(string $baseUrl, ?Client $client = null)
    {
        $this->client = $client ?: new Client(['base_uri' => $baseUrl]);
        $this->baseUrl = $baseUrl;
    }

    public function getHomePage(): ResponseInterface
    {
        return $this->get('/');
    }

    public function getJoinPage(?string $token = null): ResponseInterface
    {
        $maybeQueryParams = "";

        if ($token) {
            $maybeQueryParams = "?t=$token";
        }

        return $this->get("/join$maybeQueryParams");
    }

    public function getAdminPage(): ResponseInterface
    {
        return $this->get('/admin');
    }

    private function get(string $uri): ResponseInterface
    {
        return $this->request($uri);
    }

    private function request(string $uri): ResponseInterface
    {
        return $this->client->request('GET', $this->baseUrl . $uri, $this->buildOptions());
    }

    private function buildOptions(): array
    {
        return [
            RequestOptions::HEADERS => ['Content-Type' => 'application/json'],
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::DEBUG => false,
        ];
    }
}

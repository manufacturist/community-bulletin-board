<?php

declare(strict_types=1);

namespace App\Tests\TestKit\WebApp;

use App\Controllers\RequestDTOs\AcceptInvitationDTO;
use App\Controllers\RequestDTOs\DeclineInvitationDTO;
use App\Controllers\RequestDTOs\LoginDTO;
use App\Controllers\RequestDTOs\NewInvitationDTO;
use App\Controllers\RequestDTOs\NewPostDTO;
use App\Controllers\RequestDTOs\UpdateMaxPostsDTO;
use App\Controllers\RequestDTOs\UpdateRoleDTO;
use App\Core\Crypto;
use App\Core\JSON;
use App\Core\Types\Binary;
use App\Core\Types\Moment;
use App\Domain\Models\PostInfo;
use App\Domain\Models\User;
use App\Domain\Models\UserInfo;
use App\Domain\Repositories\InvitationRepo;
use App\Domain\Repositories\UserRepo;
use App\Tests\TestKit\Faker\NearingDate;
use App\Tests\TestKit\Faker\PinColor;
use App\Tests\TestKit\Faker\Token;
use Faker\Factory;
use Faker\Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SessionCookieJar;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Random\RandomException;

class WebAppAPI
{
    public Client $client;

    private Generator $faker;
    private string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $cookieJar = new SessionCookieJar(random_bytes(16), true);

        $this->client = new Client(['base_uri' => $baseUrl, 'cookies' => $cookieJar]);
        $this->baseUrl = $baseUrl;

        $this->faker = Factory::create();
        $this->faker->addProvider(new PinColor($this->faker));
        $this->faker->addProvider(new NearingDate($this->faker));
        $this->faker->addProvider(new Token($this->faker));
    }

    public function login(LoginDTO $dto): ResponseInterface
    {
        return $this->post('/api/public/user/login', $dto);
    }

    public function loginChecked(LoginDTO $dto): void
    {
        $response = $this->post('/api/public/user/login', $dto);

        if ($response->getStatusCode() !== 204) {
            throw new \RuntimeException("API: Failed to login. " . $response->getBody()->getContents());
        }
    }

    public function acceptInvitation(AcceptInvitationDTO $dto): ResponseInterface
    {
        return $this->put('/api/public/invitation/accept', $dto);
    }

    public function acceptInvitationChecked(AcceptInvitationDTO $dto): void
    {
        $response = $this->acceptInvitation($dto);

        if ($response->getStatusCode() !== 204) {
            throw new \RuntimeException("API: Accept invitation call failed. " . $response->getBody()->getContents());
        }
    }

    public function declineInvitation(DeclineInvitationDTO $dto): ResponseInterface
    {
        return $this->put('/api/public/invitation/decline', $dto);
    }

    public function checkHealth(): string
    {
        $response = $this->get('/api/public/health');

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException("API: Health check failed.");
        }

        return $response->getBody()->getContents();
    }

    public function install(): ResponseInterface
    {
        return $this->get('/install');
    }

    public function update(): ResponseInterface
    {
        return $this->get('/api/update');
    }

    public function invite(NewInvitationDTO $dto): ResponseInterface
    {
        return $this->post('/api/user/invite', $dto);
    }

    public function inviteChecked(NewInvitationDTO $dto): ?ResponseInterface
    {
        $response = $this->invite($dto);
        return $response->getStatusCode() === 204 ? null : $response;
    }

    public function logout(): ResponseInterface
    {
        return $this->get('/api/user/logout');
    }

    public function logoutChecked(): ?ResponseInterface
    {
        $response = $this->logout();
        return $response->getStatusCode() === 204 ? null : $response;
    }

    public function createPost(NewPostDTO $dto): ResponseInterface
    {
        return $this->post('/api/post', $dto);
    }

    public function createPostChecked(?NewPostDTO $dto = null): PostInfo
    {
        $newPostDTO = $dto ?: new NewPostDTO(
            description: $this->faker->text(),
            pinColor: $this->faker->pinColor(),
            link: null,
            expiresAt: $this->faker->nearingDate(),
        );

        $response = $this->createPost($newPostDTO);

        if ($response->getStatusCode() === 201) {
            return JSON::deserialize($response->getBody()->getContents(), PostInfo::class);
        }

        throw new \RuntimeException("API: Failed to create post.");
    }

    public function deletePost(int $postId): ResponseInterface
    {
        return $this->delete("/api/post/$postId");
    }

    public function deletePostChecked(int $postId): void
    {
        $response = $this->deletePost($postId);

        if ($response->getStatusCode() !== 204) {
            throw new \RuntimeException("API: Failed to delete post.");
        }
    }

    public function deleteUser(int $userId): ResponseInterface
    {
        return $this->delete("/api/user/$userId");
    }

    public function deleteUserChecked(int $userId): void
    {
        $response = $this->deleteUser($userId);

        if ($response->getStatusCode() !== 204) {
            throw new \RuntimeException("API: Failed to delete user.");
        }
    }

    public function updateUserMaxPosts(int $userId, int $adjustment): ResponseInterface
    {
        $dto = new UpdateMaxPostsDTO($adjustment);
        return $this->patch("/api/user/$userId/max-posts", $dto);
    }

    public function updateUserMaxPostsChecked(int $userId, int $adjustment): UserInfo
    {
        $response = $this->updateUserMaxPosts($userId, $adjustment);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException("API: Failed to update user max posts. " . $response->getBody()->getContents());
        }

        return JSON::deserialize($response->getBody()->getContents(), UserInfo::class);
    }

    public function updateUserRole(int $userId, string $role): ResponseInterface
    {
        $dto = new UpdateRoleDTO($role);
        return $this->patch("/api/user/$userId/role", $dto);
    }

    public function updateUserRoleChecked(int $userId, string $role): UserInfo
    {
        $response = $this->updateUserRole($userId, $role);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException("API: Failed to update user role. " . $response->getBody()->getContents());
        }

        return JSON::deserialize($response->getBody()->getContents(), UserInfo::class);
    }

    /**
     * @return array{User, string}
     * @throws RandomException
     */
    public function createUser(bool $isAdmin): array
    {
        $email = $this->faker->email();
        $password = $this->faker->password(8);
        $name = $this->faker->firstName();

        $adminInvitationToken = Binary::apply(random_bytes(16));
        InvitationRepo::insertInvitation($email, $adminInvitationToken, $isAdmin, Moment::now());

        $acceptInvitation = new AcceptInvitationDTO(
            token: base64_encode($adminInvitationToken->value),
            name: $name,
            phoneNumber: $this->faker->phoneNumber(),
            password: $password
        );

        $this->acceptInvitationChecked($acceptInvitation);

        return [UserRepo::selectUserByEmailHash(Crypto::hash($email)), $password];
    }

    public function createAuthenticatedUser(bool $isAdmin): UserInfo
    {
        [$user, $password] = $this->createUser($isAdmin);
        $userInfo = UserInfo::fromUser($user);

        $this->loginChecked(new LoginDTO($userInfo->email, $password));

        return $userInfo;
    }

    // Guzzle / Http related
    private function get(string $uri): ResponseInterface
    {
        return $this->request('GET', $uri, $this->buildOptions());
    }

    private function post(string $uri, object $dto): ResponseInterface
    {
        return $this->request('POST', $uri, $this->buildOptions($dto));
    }

    private function put(string $uri, object $dto): ResponseInterface
    {
        return $this->request('PUT', $uri, $this->buildOptions($dto));
    }

    private function patch(string $uri, ?object $dto): ResponseInterface
    {
        return $this->request('PATCH', $uri, $this->buildOptions($dto));
    }

    private function delete(string $uri): ResponseInterface
    {
        return $this->request('DELETE', $uri, $this->buildOptions());
    }

    private function request(string $method, string $uri, array $options): ResponseInterface
    {
        return $this->client->request($method, $this->baseUrl . $uri, $options);
    }

    private function buildOptions(?object $dto = null): array
    {
        $options = [
            RequestOptions::HEADERS => ['Content-Type' => 'application/json'],
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::DEBUG => false,
        ];

        if ($dto) {
            $options[RequestOptions::JSON] = (array)$dto;
        }

        return $options;
    }
}

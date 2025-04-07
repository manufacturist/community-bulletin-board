<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AdminController;
use App\Controllers\HttpErrorHandler;
use App\Controllers\PostController;
use App\Controllers\SetupController;
use App\Controllers\UserController;
use App\Core\Types\Base64String;
use App\Core\Types\Binary;
use App\Domain\Models\UserInfo;
use App\Domain\Repositories\InvitationRepo;
use App\Middleware\AuthMiddleware;
use App\Services\CleanupService;
use App\Services\PostService;
use App\Services\UserService;
use App\Services\VettingService;
use DI\Container;
use Dotenv\Dotenv;
use PhpMyAdmin\Twig\Extensions\I18nExtension;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

$rootDir = dirname(__DIR__);

// Setup environment
$dotenv = Dotenv::createImmutable($rootDir);
$dotenv->load();

// Setup i18n
$desiredLocale = $_ENV['APP_LOCALE'] ?? 'en_US';

putenv("LC_ALL=$desiredLocale.UTF-8");
setlocale(LC_ALL, "$desiredLocale.UTF-8");
bindtextdomain('i18n', "$rootDir/i18n");
textdomain('i18n');

// Clean-up with 1% trigger chance per request
if (rand(1, 100) == 1) {
    CleanupService::cleanup();
}

// Setup WebApp
$container = new Container();

$container->set(Twig::class, function () use ($rootDir, $desiredLocale) {
    $twig = Twig::create("$rootDir/templates", ['cache' => false]);

    $bcp47Locale = str_replace('_', '-', _('locale'));
    $twig->getEnvironment()->addGlobal('locale', $bcp47Locale);
    $twig->getEnvironment()->addGlobal('theme', 'cork');
    $twig->addExtension(new I18nExtension());

    return $twig;
});

$container->set(AuthMiddleware::class, new AuthMiddleware());

$app = AppFactory::createFromContainer($container);

$app->addBodyParsingMiddleware();
$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));

// Web pages
$app->get('/', function (Request $request, Response $response) use ($app) {
    $twig = $app->getContainer()->get(Twig::class);

    /** @var UserInfo $authenticatedUser */
    $authenticatedUser = $request->getAttribute('user');

    if ($authenticatedUser) {
        $posts = PostService::fetchAllNewestFirst();

        $currentUserPosts = count(array_filter($posts, fn($p) => $p->userId === $authenticatedUser->id));
        $isAddingPostDisabled = $currentUserPosts >= $authenticatedUser->maxActivePosts;

        return $twig->render($response->withHeader('Content-Type', 'text/html; charset=UTF-8'), 'home.twig', [
            'user' => $authenticatedUser,
            'posts' => $posts,
            'addPostAttribute' => $isAddingPostDisabled ? 'disabled' : null
        ]);
    } else {
        return $twig->render($response, 'login.twig');
    }
})->add($app->getContainer()->get(AuthMiddleware::class));

$app->get('/admin', function (Request $request, Response $response) use ($app) {
    /** @var UserInfo $authenticatedUser */
    $authenticatedUser = $request->getAttribute('user');

    if ($authenticatedUser && $authenticatedUser->isAdmin()) {
        $activeInvitations = VettingService::getInvitations();
        $members = UserService::fetchAll();

        return $app->getContainer()->get(Twig::class)->render($response, 'admin.twig', [
            'user' => $authenticatedUser,
            'invitations' => $activeInvitations,
            'members' => $members
        ]);
    } else {
        throw new HttpNotFoundException($request);
    }
})->add($app->getContainer()->get(AuthMiddleware::class));;

$app->get('/' . _('url_join'), function (Request $request, Response $response) use ($app) {
    $token = $request->getQueryParams()['t'] ?? null;
    if ($token) {
        $binaryToken = Binary::apply(Base64String::apply($token)->decode());
        $invitation = InvitationRepo::selectInvitationByToken($binaryToken);

        return $app->getContainer()->get(Twig::class)->render($response, 'join.twig', [
            'email' => $invitation->email,
        ]);
    } else {
        throw new HttpNotFoundException($request);
    }
});

// One-time setup endpoint; breaking REST convention for user convenience
$app->get('/install', SetupController::class . ':setup');

// API routes
$app->group('/api', function (RouteCollectorProxy $apiGroup) use ($app) {

    // Public endpoints
    $apiGroup->group('/public', function (RouteCollectorProxy $publicGroup) use ($app) {
        $publicGroup->get('/health', function (Request $request, Response $response) {
            return $response->withStatus(200)->write("OK");
        });

        $publicGroup->post('/user/login', UserController::class . ':login');
        $publicGroup->put('/invitation/accept', UserController::class . ':acceptInvitation');
        $publicGroup->put('/invitation/decline', UserController::class . ':declineInvitation');
    });

    // Authenticated endpoints
    $apiGroup->group('', function (RouteCollectorProxy $apiGroup) use ($app) {
        $apiGroup->post('/invite', AdminController::class . ':invite');
        $apiGroup->delete('/user/{userId}', AdminController::class . ':deleteUser');
        $apiGroup->patch('/user/{userId}/max-posts', AdminController::class . ':updateUserMaxPosts');
        $apiGroup->patch('/user/{userId}/role', AdminController::class . ':updateUserRole');
        $apiGroup->put('/user/logout', UserController::class . ':logout');
        $apiGroup->post('/post', PostController::class . ':createPost');
        $apiGroup->delete('/post/{postId}', PostController::class . ':deletePost');
    })->add($app->getContainer()->get(AuthMiddleware::class));
});

$app->addRoutingMiddleware();

$callableResolver = $app->getCallableResolver();
$responseFactory = $app->getResponseFactory();
$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

$app->run();

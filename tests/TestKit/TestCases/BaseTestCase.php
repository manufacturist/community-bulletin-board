<?php

declare(strict_types=1);
declare(ticks=1);

namespace App\Tests\TestKit\TestCases;

use App\Core\Crypto;
use App\Core\MariaTransactor;
use App\Core\Types\Base64String;
use App\Core\Types\Moment;
use App\Core\Types\SystemTheme;
use App\Domain\Models\UserInfo;
use App\Domain\Repositories\UserRepo;
use App\Services\AuthService;
use App\Tests\TestKit\Faker\NearingDate;
use App\Tests\TestKit\Faker\PinColor;
use App\Tests\TestKit\Faker\Token;
use App\Tests\TestKit\ReleaseHandler;
use Docker\Docker;
use Dotenv\Dotenv;
use Faker\Factory;
use Faker\Generator;
use Testcontainers\Container\StartedGenericContainer;
use Testcontainers\Modules\MariaDBContainer;

abstract class BaseTestCase extends DockerNetworkTestCase
{
    protected static ?StartedGenericContainer $mariaDBContainer = null;

    public static ?Generator $faker = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (is_null(self::$mariaDBContainer)) {

            // Setup faker
            self::$faker = Factory::create();
            self::$faker->addProvider(new PinColor(self::$faker));
            self::$faker->addProvider(new NearingDate(self::$faker));
            self::$faker->addProvider(new Token(self::$faker));

            $rootDir = dirname(__DIR__, 3);

            // Setup query-migration environment
            $dotenv = Dotenv::createImmutable($rootDir);
            $dotenv->load();

            // Setup i18n
            $desiredLocale = 'en_US';
            putenv("LC_ALL=$desiredLocale.UTF-8");
            setlocale(LC_ALL, "$desiredLocale.UTF-8");
            bindtextdomain('i18n', $rootDir . '/i18n');
            bind_textdomain_codeset('i18n', 'UTF-8');
            textdomain('i18n');

            // Setup db
            self::$mariaDBContainer = new MariaDBContainer('10.11')
                ->withName('mariadb-cbb')
                ->withNetwork(self::$networkName)
                ->withMariaDBDatabase($_ENV["DB_NAME"])
                ->withMariaDBUser($_ENV["DB_USERNAME"], $_ENV["DB_PASSWORD"])
                ->start();

            $_ENV["DB_HOST"] = self::$mariaDBContainer->getHost();
            $_ENV["DB_PORT"] = self::$mariaDBContainer->getMappedPort(3306);

            try {
                self::createTestTables();

                ReleaseHandler::registerHandler(
                    id: 'mariadb',
                    callable: self::releaseResources(...),
                    priority: ReleaseHandler::PRIORITY_MID
                );

            } catch (\Exception $e) {
                self::fail('MariaDB setup failed: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            }

            // Allow graceful shutdown and resources release
            pcntl_signal(SIGINT, fn() => exit(1));
            register_shutdown_function(ReleaseHandler::executeHandlers(...));
        }
    }

    public static function releaseResources(): void
    {
        $dockerClient = Docker::create();

        $container = $dockerClient->containerInspect(self::$mariaDBContainer->getName());

        self::$mariaDBContainer->stop();

        // NOTE: I do not understand why the MariaDB volume persists. What am I missing? Removing it manually
        if ($container) {
            try {
                echo "Deleting MariaDB volume\n";

                $volumeName = $container->getMounts()[0]->getName();
                $dockerClient->volumeDelete($volumeName, fetch: Docker::FETCH_RESPONSE);

                echo "Deleted MariaDB volume\n";
            } catch (\Exception $e) {
                echo "Failed to delete MariaDB volume: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * @throws \Exception
     */
    private static function createTestTables(): void
    {
        $rootDir = dirname(__DIR__, 3);
        $migrationsPattern = "$rootDir/database/query-migration/*.sql";

        $sqlFiles = glob($migrationsPattern);
        if (empty($sqlFiles)) {
            throw new \Exception("No SQL files were found.");
        }

        foreach ($sqlFiles as $file) {
            $sql = file_get_contents($file);
            $isSuccessfullyApplied = MariaTransactor::update($sql);
            echo "Applied migration ($isSuccessfullyApplied) $file\n";
        }
    }

    /**
     * @return array{?UserInfo, Base64String}
     */
    protected static function addAuthenticatedAdmin(): array
    {
        return self::addCustomAuthenticatedUser(
            name: self::$faker->firstName(),
            email: self::$faker->email(),
            phoneNumber: self::$faker->phoneNumber(),
            role: 'admin'
        );
    }

    /**
     * @return array{?UserInfo, Base64String}
     */
    protected static function addAuthenticatedUser(): array
    {
        return self::addCustomAuthenticatedUser(
            name: self::$faker->firstName(),
            email: self::$faker->email(),
            phoneNumber: self::$faker->phoneNumber()
        );
    }

    /**
     * @return array{?UserInfo, Base64String}
     */
    protected static function addCustomAuthenticatedUser(
        string $name,
        string $email,
        string $phoneNumber,
        string $role = 'member',
        int    $maxActivePosts = 2
    ): array
    {
        $password = "42";
        $emailHash = Crypto::hash($email);

        UserRepo::insertUser(
            encryptedName: Crypto::encrypt($name),
            encryptedEmail: Crypto::encrypt($email),
            encryptedPhoneNumber: Crypto::encrypt($phoneNumber),
            emailHash: $emailHash,
            passwordHash: password_hash($password, PASSWORD_BCRYPT),
            maxActivePosts: $maxActivePosts,
            theme: SystemTheme::CORK,
            role: $role,
            createdAt: Moment::now()
        );

        try {
            $userToken = AuthService::login($email, $password)[0];

            if ($userToken) {
                return [UserInfo::fromUser(UserRepo::selectUserByEmailHash($emailHash)), $userToken];
            }

            self::fail("Authentication failed :(");
        } catch (\Exception $e) {
            self::fail("Failed to login: " . $e->getMessage());
        }
    }
}

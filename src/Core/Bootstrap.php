<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use OpenPulse\Contracts\QueueInterface;
use OpenPulse\Repositories\MySqlQueue;
use PDO;
use Predis\ClientInterface;

final class Bootstrap
{
    public static function createContainer(): Container
    {
        $projectRoot = dirname(__DIR__, 2);

        require_once $projectRoot . '/config/environment.php';

        $pdo = require $projectRoot . '/config/database.php';
        $redisConfig = require $projectRoot . '/config/redis.php';

        $container = new Container();

        // Register infrastructure that cannot be autowired.
        $container->set(PDO::class, $pdo);

        $redisFactory = new RedisClientFactory();

        $container->factory(
            ClientInterface::class,
            static fn (): ClientInterface => $redisFactory->create($redisConfig)
        );

        $services = require $projectRoot . '/config/services.php';

        foreach ($services as $id => $concrete) {
            $container->bind($id, $concrete);
        }

        // Register queue configuration and its configured implementation.
        $queueConfig = QueueConfig::fromEnvironment();

        $container->set(QueueConfig::class, $queueConfig);

        $container->set(
            QueueInterface::class,
            new MySqlQueue(
                $pdo,
                $queueConfig->reservationTimeoutSeconds
            )
        );

        return $container;
    }

    public static function createApplication(): Application
    {
        $container = self::createContainer();
        $router = new Router();

        return new Application($router, $container);
    }
}

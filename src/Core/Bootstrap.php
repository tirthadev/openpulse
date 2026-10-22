<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use PDO;
use Predis\Client;
use Predis\ClientInterface;

final class Bootstrap
{
    public static function createContainer(): Container
    {
        $projectRoot = dirname(__DIR__, 2);

        $pdo = require $projectRoot . '/config/database.php';
        $redisConfig = require $projectRoot . '/config/redis.php';

        $container = new Container();

        // Register infrastructure that cannot be autowired.
        $container->set(PDO::class, $pdo);

        $redis = new Client([
            'scheme' => $redisConfig['scheme'],
            'host' => $redisConfig['host'],
            'port' => $redisConfig['port'],
            'password' => $redisConfig['password'] !== ''
                ? $redisConfig['password']
                : null,
            'database' => $redisConfig['database'],
        ]);

        $container->set(ClientInterface::class, $redis);

        // Load service bindings from configuration.
        $services = require $projectRoot . '/config/services.php';

        foreach ($services as $id => $concrete) {
            $container->bind($id, $concrete);
        }

        return $container;
    }

    public static function createApplication(): Application
    {
        $container = self::createContainer();
        $router = new Router();

        return new Application($router, $container);
    }
}

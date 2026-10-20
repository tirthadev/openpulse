<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use OpenPulse\Contracts\EventRepositoryInterface;
use OpenPulse\Repositories\MySqlDataRepository;
use OpenPulse\Repositories\MySqlEventRepository;
use OpenPulse\Services\AlternativeDataProvider;
use OpenPulse\Services\DataCollectionService;

final class Bootstrap
{
    public static function createApplication(): Application
    {
        $pdo = require dirname(__DIR__, 2) . '/config/database.php';

        $database = new Database($pdo);

        $dataRepository = new MySqlDataRepository($database);
        $dataProvider = new AlternativeDataProvider();

        $eventRepository = new MySqlEventRepository($database);

        $dataCollectionService = new DataCollectionService(
            $dataProvider,
            $dataRepository
        );

        $container = new Container();

        $container->set(\PDO::class, $pdo);
        $container->set(Database::class, $database);

        $container->set(
            EventRepositoryInterface::class,
            $eventRepository
        );

        $container->set(
            DataCollectionService::class,
            $dataCollectionService
        );

        $router = new Router();

        return new Application($router, $container);
    }
}

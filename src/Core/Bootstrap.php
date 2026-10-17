<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use OpenPulse\Services\AlternativeDataProvider;
use OpenPulse\Repositories\MySqlDataRepository;
use OpenPulse\Services\DataCollectionService;
use OpenPulse\Services\EventValidator;
use OpenPulse\Repositories\MySqlEventRepository;

final class Bootstrap
{
    public static function createApplication(): Application
    {
        $pdo = require dirname(__DIR__, 2) . '/config/database.php';

        $database = new Database($pdo);

        $repository = new MySqlDataRepository($database);

        $dataProvider = new AlternativeDataProvider();

        $eventRepository = new MySqlEventRepository($database);

        $dataCollectionService = new DataCollectionService(
            $dataProvider,
            $repository
        );
        $router = new Router();

        return new Application($dataCollectionService, $router, new EventValidator(), $eventRepository);
    }
}

<?php

declare(strict_types=1);

use OpenPulse\Commands\ImportCountriesCommand;
use OpenPulse\Commands\MigrateCommand;
use OpenPulse\Contracts\CommandInterface;
use OpenPulse\Core\Container;
use OpenPulse\Commands\QueueWorkCommand;
use OpenPulse\Contracts\QueueInterface;
use OpenPulse\Core\JobHandlerRegistry;
use OpenPulse\Core\Worker;
use OpenPulse\Jobs\CountryImportJobHandler;
use OpenPulse\Commands\QueuePushCommand;

$projectRoot = dirname(__DIR__);

return [
    'migrate' => static function (Container $container) use ($projectRoot): CommandInterface {
        return new MigrateCommand(
            $container->get(\PDO::class),
            $projectRoot . '/database/migrations'
        );
    },

    'countries:import' => ImportCountriesCommand::class,

    'queue:work' => static function (
        Container $container
    ): CommandInterface {
        $registry = new JobHandlerRegistry([
            'country.import' => $container->get(
                CountryImportJobHandler::class
            ),
        ]);

        return new QueueWorkCommand(
            new Worker(
                $container->get(QueueInterface::class),
                $registry,
            ),
        );
    },

    'queue:push' => static function (
        Container $container
    ): CommandInterface {
        return new QueuePushCommand(
            $container->get(QueueInterface::class),
        );
    },
];
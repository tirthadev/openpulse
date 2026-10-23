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
use OpenPulse\Core\QueueConfig;
use OpenPulse\Commands\ProfileCommand;
use OpenPulse\Core\Profiler;

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

        $queueConfig = $container->get(QueueConfig::class);

        if (!$queueConfig instanceof QueueConfig) {
            throw new \LogicException(
                'Queue configuration is not registered.'
            );
        }

        return new QueueWorkCommand(
            new Worker(
                $container->get(QueueInterface::class),
                $registry,
                maxAttempts: $queueConfig->maxAttempts,
                retryDelaySeconds: $queueConfig->retryDelaySeconds,
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

    'profile' => static function (
        Container $container
    ): CommandInterface {
        return new ProfileCommand(
            $container->get(\PDO::class),
            new Profiler(),
        );
    },
];
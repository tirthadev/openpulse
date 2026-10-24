<?php

declare(strict_types=1);

use OpenPulse\Commands\ImportCountriesCommand;
use OpenPulse\Commands\MigrateCommand;
use OpenPulse\Contracts\CommandInterface;
use OpenPulse\Contracts\QueueInterface;
use OpenPulse\Core\Container;
use OpenPulse\Core\JobHandlerRegistry;
use OpenPulse\Core\Profiler;
use OpenPulse\Core\QueueConfig;
use OpenPulse\Core\Worker;
use OpenPulse\Commands\ProfileCommand;
use OpenPulse\Commands\QueuePushCommand;
use OpenPulse\Commands\QueueWorkCommand;
use OpenPulse\Jobs\CountryImportJobHandler;
use OpenPulse\Jobs\OpenAlexImportJobHandler;
use OpenPulse\Commands\QueueRetryFailedCommand;

$projectRoot = dirname(__DIR__);

return [
    'migrate' => static function (
        Container $container
    ) use ($projectRoot): CommandInterface {
        return new MigrateCommand(
            $container->get(\PDO::class),
            $projectRoot . '/database/migrations',
        );
    },

    'countries:import' => ImportCountriesCommand::class,

    'queue:work' => static function (
        Container $container
    ): CommandInterface {
        $registry = new JobHandlerRegistry([
            'country.import' => $container->get(
                CountryImportJobHandler::class,
            ),
            'openalex.import' => $container->get(
                OpenAlexImportJobHandler::class,
            ),
        ]);

        $queueConfig = $container->get(QueueConfig::class);

        if (!$queueConfig instanceof QueueConfig) {
            throw new LogicException(
                'Queue configuration is not registered.',
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

    'queue:retry-failed' => static function (
        Container $container
    ): CommandInterface {
        return new QueueRetryFailedCommand(
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
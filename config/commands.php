<?php

declare(strict_types=1);

use OpenPulse\Commands\ImportCountriesCommand;
use OpenPulse\Commands\MigrateCommand;
use OpenPulse\Contracts\CommandInterface;
use OpenPulse\Core\Container;

$projectRoot = dirname(__DIR__);

return [
    'migrate' => static function (Container $container) use ($projectRoot): CommandInterface {
        return new MigrateCommand(
            $container->get(\PDO::class),
            $projectRoot . '/database/migrations'
        );
    },

    'countries:import' => ImportCountriesCommand::class,
];
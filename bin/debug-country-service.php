<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use OpenPulse\Core\Bootstrap;
use OpenPulse\Services\CountryImportService;
use OpenPulse\Contracts\CountryProviderInterface;
use OpenPulse\Contracts\CountryRepositoryInterface;

$container = Bootstrap::createContainer();

$service = $container->get(CountryImportService::class);
$provider = $container->get(CountryProviderInterface::class);
$repository = $container->get(CountryRepositoryInterface::class);

echo get_class($service) . PHP_EOL;
echo get_class($provider) . PHP_EOL;
echo get_class($repository) . PHP_EOL;
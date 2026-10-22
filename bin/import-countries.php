<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use OpenPulse\Core\Bootstrap;
use OpenPulse\Services\CountryImportService;

try {
    $container = Bootstrap::createContainer();

    $importService = $container->get(
        CountryImportService::class
    );

    $saved = $importService->import();

    echo "Import completed successfully.\n";
    echo "Records processed: {$saved}\n";
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        'Import failed: ' . $exception->getMessage() . PHP_EOL
    );

    exit(1);
}
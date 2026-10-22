<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use OpenPulse\Services\WorldBankCountryProvider;

try {
    $provider = new WorldBankCountryProvider();
    $countries = $provider->fetch();

    echo "Country API fetched successfully\n";
    echo "================================\n";
    echo 'Countries fetched: ' . count($countries) . PHP_EOL;
    echo PHP_EOL;

    echo "First five countries\n";
    echo "--------------------\n";

    foreach (array_slice($countries, 0, 5) as $country) {
        printf(
            "%s (%s) | Capital: %s | Region: %s\n",
            $country['name'],
            $country['code'],
            $country['capital'] !== ''
                ? $country['capital']
                : 'N/A',
            $country['region'] !== ''
                ? $country['region']
                : 'N/A'
        );
    }
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        'Error: ' . $exception->getMessage() . PHP_EOL
    );

    exit(1);
}
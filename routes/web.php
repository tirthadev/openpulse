<?php
declare(strict_types=1);

use OpenPulse\Controllers\EventController;
use OpenPulse\Controllers\CountryController;
use OpenPulse\Core\Container;
use OpenPulse\Core\Router;

return static function (
    Router $router,
    Container $container
): void {
    //Controller Initializations
    $events = $container->get(EventController::class);
    $countries = $container->get(CountryController::class);

    //Routes Initializations
    $router->post('/api/events', [$events, 'store']);
    $router->get('/api/events', [$events, 'index']);

    $router->get('/api/countries', [$countries, 'index']);

    $router->get('/health', static fn (): array => [
        'status' => 'ok',
        'application' => 'OpenPulse Running from GCP',
    ]);
};

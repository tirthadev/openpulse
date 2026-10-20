<?php
declare(strict_types=1);

use OpenPulse\Controllers\EventController;
use OpenPulse\Core\Container;
use OpenPulse\Core\Router;

return static function (
    Router $router,
    Container $container
): void {
    $events = $container->get(EventController::class);

    $router->post('/api/events', [$events, 'store']);
    $router->get('/api/events', [$events, 'index']);
};

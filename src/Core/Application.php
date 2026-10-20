<?php

declare(strict_types=1);

namespace OpenPulse\Core;

final class Application
{
    public function __construct(
        private readonly Router $router,
        private readonly Container $container
    ) {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $registerRoutes = require dirname(__DIR__, 2)
            . '/routes/web.php';

        $registerRoutes($this->router, $this->container);
    }

    public function run(Request $request): Response
    {
        return $this->router->dispatch($request);
    }
}

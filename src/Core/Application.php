<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use OpenPulse\Contracts\DataCollectionInterface;

final class Application
{
    public function __construct(
        private readonly DataCollectionInterface $dataCollectionService,
        private readonly Router $router
    ) {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $this->router->get('/', function (): array {
            return $this->dataCollectionService->collect();
        });

        $this->router->get('/api/health', function (): array {
            return ['status' => 'ok'];
        });

        $this->router->post('/api/events', function (): array {
            return ['message' => 'Event received', 'method' => 'POST'];
        });
    }

    public function run(Request $request): Response
    {
        return $this->router->dispatch($request);
    }
}

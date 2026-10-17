<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use OpenPulse\Contracts\DataCollectionInterface;
use OpenPulse\Services\EventValidator;
use OpenPulse\Contracts\EventRepositoryInterface;

final class Application
{
    public function __construct(
        private readonly DataCollectionInterface $dataCollectionService,
        private readonly Router $router,
        private readonly EventValidator $eventValidator,
        private readonly EventRepositoryInterface $eventRepository
    ) {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $this->router->get('/', function (Request $request): array {
            return $this->dataCollectionService->collect();
        });

        $this->router->get('/api/health', function (Request $request): array {
            return ['status' => 'ok'];
        });

        $this->router->post('/api/events', function (Request $request): array {
            $body = $request->getBody();
            $this->eventValidator->validate($body);
            $event = $body['event'];
            $this->eventRepository->save($body);

            return [
                'message' => 'Event received',
                'event' => $event,
            ];
        });
    }

    public function run(Request $request): Response
    {
        return $this->router->dispatch($request);
    }
}

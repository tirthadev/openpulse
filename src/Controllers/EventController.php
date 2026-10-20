<?php

declare(strict_types=1);

namespace OpenPulse\Controllers;

use OpenPulse\Services\EventValidator;
use OpenPulse\Contracts\EventRepositoryInterface;
use OpenPulse\Core\Request;

final class EventController
{
    public function __construct(
        private readonly EventValidator $eventValidator,
        private readonly EventRepositoryInterface $eventRepository
    ) {
    }

    public function index(Request $request): array
    {
        return [
            'events' => $this->eventRepository->findAll(),
        ];
    }

    public function store(Request $request): array
    {
        $data = $this->eventValidator->validate(
            $request->getBody()
        );

        $this->eventRepository->save($data);

        return [
            'message' => 'Event received',
            'event' => $data['event'],
        ];
    }
}

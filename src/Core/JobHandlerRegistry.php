<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use OpenPulse\Contracts\JobHandlerInterface;
use RuntimeException;

final class JobHandlerRegistry
{
    /**
     * @param array<string, JobHandlerInterface> $handlers
     */
    public function __construct(
        private readonly array $handlers,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(string $type, array $payload): void
    {
        if (!isset($this->handlers[$type])) {
            throw new RuntimeException(
                sprintf('No handler registered for job type "%s".', $type)
            );
        }

        $this->handlers[$type]->handle($payload);
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return array_keys($this->handlers);
    }
}

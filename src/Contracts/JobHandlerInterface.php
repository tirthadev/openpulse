<?php

declare(strict_types=1);

namespace OpenPulse\Contracts;

interface JobHandlerInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload): void;
}

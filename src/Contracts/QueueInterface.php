<?php

declare(strict_types=1);

namespace OpenPulse\Contracts;

interface QueueInterface
{
    public function push(
        string $jobType,
        array $payload,
        string $queue = 'default',
    ): int;

    public function reserve(
        string $queue = 'default',
    ): ?array;

    public function delete(int $jobId): void;

    public function release(int $jobId, int $delaySeconds = 0): void;

    public function fail(int $jobId, string $exception): void;
}
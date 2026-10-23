<?php

declare(strict_types=1);

namespace OpenPulse\Commands;

use OpenPulse\Contracts\CommandInterface;
use OpenPulse\Core\Worker;

final class QueueWorkCommand implements CommandInterface
{
    public function __construct(
        private readonly Worker $worker,
    ) {
    }

    public function execute(array $arguments = []): int
    {
        $queueName = $arguments[0] ?? 'default';

        if (!is_string($queueName) || trim($queueName) === '') {
            fwrite(STDERR, "Queue name must not be empty.\n");

            return 1;
        }

        try {
            $processed = $this->worker->runOnce($queueName);
        } catch (\Throwable $exception) {
            fwrite(
                STDERR,
                sprintf("Queue worker failed: %s\n", $exception->getMessage()),
            );

            return 1;
        }

        if (!$processed) {
            fwrite(STDOUT, "No available jobs in queue: {$queueName}\n");

            return 0;
        }

        fwrite(STDOUT, "Processed one job from queue: {$queueName}\n");

        return 0;
    }
}
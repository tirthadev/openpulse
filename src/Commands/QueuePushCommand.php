<?php

declare(strict_types=1);

namespace OpenPulse\Commands;

use OpenPulse\Contracts\CommandInterface;
use OpenPulse\Contracts\QueueInterface;

final class QueuePushCommand implements CommandInterface
{
    public function __construct(
        private readonly QueueInterface $queue,
    ) {
    }

    public function execute(array $arguments = []): int
    {
        $jobType = $arguments[0] ?? '';

        if ($jobType !== 'country.import') {
            fwrite(
                STDERR,
                "Usage: openpulse queue:push country.import\n",
            );

            return 1;
        }

        $jobId = $this->queue->push($jobType, []);

        fwrite(STDOUT, "Queued job with ID: {$jobId}\n");

        return 0;
    }
}
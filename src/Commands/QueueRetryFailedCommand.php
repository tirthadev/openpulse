<?php

declare(strict_types=1);

namespace OpenPulse\Commands;

use InvalidArgumentException;
use OpenPulse\Contracts\CommandInterface;
use OpenPulse\Contracts\QueueInterface;

final class QueueRetryFailedCommand implements CommandInterface
{
    public function __construct(
        private readonly QueueInterface $queue,
    ) {
    }

    /**
     * @param list<string> $arguments
     */
    public function execute(array $arguments = []): int
    {
        if (count($arguments) !== 1) {
            $this->printUsage();

            return 1;
        }

        $failedJobId = filter_var(
            $arguments[0],
            FILTER_VALIDATE_INT,
        );

        if (!is_int($failedJobId) || $failedJobId < 1) {
            fwrite(
                STDERR,
                "Failed job ID must be a positive integer.\n",
            );

            return 1;
        }

        $failedJob = $this->queue->findFailed($failedJobId);

        if ($failedJob === null) {
            fwrite(
                STDERR,
                "Failed job {$failedJobId} was not found.\n",
            );

            return 1;
        }

        $payload = $failedJob['payload'];

        $jobType = $payload['type'];
        $jobData = $payload['data'];

        try {
            $newJobId = $this->queue->push(
                $jobType,
                $jobData,
                $failedJob['queue'],
            );
        } catch (InvalidArgumentException $exception) {
            fwrite(
                STDERR,
                "Could not retry failed job: {$exception->getMessage()}\n",
            );

            return 1;
        }

        fwrite(
            STDOUT,
            sprintf(
                "Requeued failed job %d as new job %d on queue '%s'.\n",
                $failedJobId,
                $newJobId,
                $failedJob['queue'],
            ),
        );

        return 0;
    }

    private function printUsage(): void
    {
        fwrite(
            STDERR,
            "Usage: openpulse queue:retry-failed <failed-job-id>\n",
        );
    }
}
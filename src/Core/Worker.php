<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use OpenPulse\Contracts\QueueInterface;
use Throwable;
use InvalidArgumentException;

final class Worker
{
    public function __construct(
        private readonly QueueInterface $queue,
        private readonly JobHandlerRegistry $handlers,
        private readonly int $maxAttempts = 3,
        private readonly int $retryDelaySeconds = 30,
    ) {
        if ($this->maxAttempts < 1) {
            throw new InvalidArgumentException(
                'Maximum attempts must be at least one.'
            );
        }

        if ($this->retryDelaySeconds < 0) {
            throw new InvalidArgumentException(
                'Retry delay cannot be negative.'
            );
        }
    }

    
    public function runOnce(string $queueName = 'default'): bool
    {
        $job = $this->queue->reserve($queueName);

        if ($job === null) {
            return false;
        }

        $jobId = $job['id'];
        $attempts = $job['attempts'];
        $payload = $job['payload'];

        try {
            if (
                !isset($payload['type'], $payload['data'])
                || !is_string($payload['type'])
                || !is_array($payload['data'])
            ) {
                throw new \RuntimeException(
                    'Invalid job payload structure.'
                );
            }

            $this->handlers->handle(
                $payload['type'],
                $payload['data']
            );
        } catch (Throwable $exception) {
            if ($attempts >= $this->maxAttempts) {
                $this->queue->fail(
                    $jobId,
                    $exception::class . ': ' . $exception->getMessage(),
                );
            } else {
                $this->queue->release(
                    $jobId,
                    $this->retryDelaySeconds,
                );
            }

            return true;
        }

        // Acknowledge only after the handler succeeds.
        // If deletion fails, let the infrastructure error propagate.
        $this->queue->delete($jobId);

        return true;
    }

}
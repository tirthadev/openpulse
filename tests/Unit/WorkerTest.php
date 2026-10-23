<?php

declare(strict_types=1);

namespace OpenPulse\Tests\Unit;

use OpenPulse\Contracts\JobHandlerInterface;
use OpenPulse\Contracts\QueueInterface;
use OpenPulse\Core\JobHandlerRegistry;
use OpenPulse\Core\Worker;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class WorkerTest extends TestCase
{
    public function testSuccessfulJobIsHandledAndDeleted(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $handler = $this->createMock(JobHandlerInterface::class);

        $queue->expects(self::once())
            ->method('reserve')
            ->with('default')
            ->willReturn([
                'id' => 42,
                'queue' => 'default',
                'attempts' => 1,
                'payload' => [
                    'type' => 'country.import',
                    'data' => [],
                ],
            ]);

        $handler->expects(self::once())
            ->method('handle')
            ->with([]);

        $queue->expects(self::once())
            ->method('delete')
            ->with(42);

        $queue->expects(self::never())->method('release');
        $queue->expects(self::never())->method('fail');

        $worker = new Worker(
            $queue,
            new JobHandlerRegistry([
                'country.import' => $handler,
            ]),
        );

        self::assertTrue($worker->runOnce());
    }

    public function testFailedJobIsReleasedWhenAttemptsRemain(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $handler = $this->createMock(JobHandlerInterface::class);

        $queue->method('reserve')->willReturn([
            'id' => 42,
            'queue' => 'default',
            'attempts' => 1,
            'payload' => [
                'type' => 'country.import',
                'data' => [],
            ],
        ]);

        $handler->expects(self::once())
            ->method('handle')
            ->willThrowException(
                new RuntimeException('Temporary API failure'),
            );

        $queue->expects(self::once())
            ->method('release')
            ->with(42, 30);

        $queue->expects(self::never())->method('delete');
        $queue->expects(self::never())->method('fail');

        $worker = new Worker(
            $queue,
            new JobHandlerRegistry([
                'country.import' => $handler,
            ]),
            maxAttempts: 3,
            retryDelaySeconds: 30,
        );

        self::assertTrue($worker->runOnce());
    }

    public function testJobIsFailedAfterMaximumAttempts(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $handler = $this->createMock(JobHandlerInterface::class);

        $queue->method('reserve')->willReturn([
            'id' => 42,
            'queue' => 'default',
            'attempts' => 3,
            'payload' => [
                'type' => 'country.import',
                'data' => [],
            ],
        ]);

        $handler->expects(self::once())
            ->method('handle')
            ->willThrowException(
                new RuntimeException('Permanent import failure'),
            );

        $queue->expects(self::once())
            ->method('fail')
            ->with(
                42,
                'RuntimeException: Permanent import failure',
            );

        $queue->expects(self::never())->method('delete');
        $queue->expects(self::never())->method('release');

        $worker = new Worker(
            $queue,
            new JobHandlerRegistry([
                'country.import' => $handler,
            ]),
            maxAttempts: 3,
            retryDelaySeconds: 30,
        );

        self::assertTrue($worker->runOnce());
    }
}

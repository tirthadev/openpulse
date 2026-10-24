<?php

declare(strict_types=1);

namespace OpenPulse\Tests\Unit;

use OpenPulse\Commands\QueueRetryFailedCommand;
use OpenPulse\Contracts\QueueInterface;
use PHPUnit\Framework\TestCase;

final class QueueRetryFailedCommandTest extends TestCase
{
    public function testSuccessfulRetryQueuesOriginalJobAndPreservesHistory(): void
    {
        $queue = $this->createMock(QueueInterface::class);

        $queue->expects(self::once())
            ->method('findFailed')
            ->with(42)
            ->willReturn([
                'id' => 42,
                'queue' => 'imports',
                'payload' => [
                    'type' => 'openalex.import',
                    'data' => [
                        'cursor' => 'cursor-123',
                        'per_page' => 100,
                        'pages_remaining' => 3,
                    ],
                ],
            ]);

        $queue->expects(self::once())
            ->method('push')
            ->with(
                'openalex.import',
                [
                    'cursor' => 'cursor-123',
                    'per_page' => 100,
                    'pages_remaining' => 3,
                ],
                'imports',
            )
            ->willReturn(101);

        // The command must preserve the failed-job history.
        $queue->expects(self::never())->method('fail');

        $command = new QueueRetryFailedCommand($queue);

        self::assertSame(0, $command->execute(['42']));
    }

    public function testMissingFailedJobReturnsError(): void
    {
        $queue = $this->createMock(QueueInterface::class);

        $queue->expects(self::once())
            ->method('findFailed')
            ->with(999)
            ->willReturn(null);

        $queue->expects(self::never())->method('push');

        $command = new QueueRetryFailedCommand($queue);

        self::assertSame(1, $command->execute(['999']));
    }

    public function testInvalidIdReturnsErrorWithoutAccessingQueue(): void
    {
        $queue = $this->createMock(QueueInterface::class);

        $queue->expects(self::never())->method('findFailed');
        $queue->expects(self::never())->method('push');

        $command = new QueueRetryFailedCommand($queue);

        self::assertSame(1, $command->execute(['abc']));
        self::assertSame(1, $command->execute(['0']));
        self::assertSame(1, $command->execute(['-5']));
    }

    public function testMissingArgumentReturnsErrorWithoutAccessingQueue(): void
    {
        $queue = $this->createMock(QueueInterface::class);

        $queue->expects(self::never())->method('findFailed');
        $queue->expects(self::never())->method('push');

        $command = new QueueRetryFailedCommand($queue);

        self::assertSame(1, $command->execute([]));
    }
}

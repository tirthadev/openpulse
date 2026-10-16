<?php

declare(strict_types=1);

namespace OpenPulse\Tests\Unit;

use OpenPulse\Contracts\DataCollectionInterface;
use OpenPulse\Core\Application;
use PHPUnit\Framework\TestCase;

final class ApplicationExceptionTest extends TestCase
{
    public function testRunPropagatesCollectionException(): void
    {
        $dataCollection = $this->createMock(
            DataCollectionInterface::class
        );

        $dataCollection
            ->expects($this->once())
            ->method('collect')
            ->willThrowException(
                new \RuntimeException('Collection failed')
            );

        $application = new Application($dataCollection);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Collection failed');

        $application->run();
    }
}
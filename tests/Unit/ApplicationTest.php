<?php

declare(strict_types=1);

namespace OpenPulse\Tests\Unit;

use OpenPulse\Contracts\DataCollectionInterface;
use OpenPulse\Core\Application;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    public function testRunReturnsCollectedData(): void
    {
        $expectedData = [
            'source' => 'test',
            'count' => 42,
        ];

        $dataCollection = $this->createMock(
            DataCollectionInterface::class
        );

        $dataCollection
            ->expects($this->once())
            ->method('collect')
            ->willReturn($expectedData);

        $application = new Application($dataCollection);

        $result = $application->run();

        $this->assertSame($expectedData, $result);
    }
}
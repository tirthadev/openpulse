<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use OpenPulse\Contracts\CacheInterface;
use OpenPulse\Contracts\CountryProviderInterface;
use OpenPulse\Contracts\CountryRepositoryInterface;
use OpenPulse\Services\CountryImportService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

final class CountryImportServiceTest extends TestCase
{
    /** @var CountryProviderInterface&MockObject */
    private CountryProviderInterface $countryProvider;

    /** @var CountryRepositoryInterface&MockObject */
    private CountryRepositoryInterface $countryRepository;

    /** @var CacheInterface&MockObject */
    private CacheInterface $cache;

    private CountryImportService $service;

    protected function setUp(): void
    {
        $this->countryProvider = $this->createMock(
            CountryProviderInterface::class
        );

        $this->countryRepository = $this->createMock(
            CountryRepositoryInterface::class
        );

        $this->cache = $this->createMock(
            CacheInterface::class
        );

        $this->service = new CountryImportService(
            $this->countryProvider,
            $this->countryRepository,
            $this->cache
        );
    }

    public function testImportFetchesAndSavesCountriesAndInvalidatesCache(): void
    {
        $countries = [
            [
                'code' => 'IN',
                'name' => 'India',
                'capital' => 'New Delhi',
                'region' => 'South Asia',
            ],
            [
                'code' => 'GB',
                'name' => 'United Kingdom',
                'capital' => 'London',
                'region' => 'Europe & Central Asia',
            ],
        ];

        $this->countryProvider
            ->expects($this->once())
            ->method('fetch')
            ->willReturn($countries);

        $this->countryRepository
            ->expects($this->once())
            ->method('saveMany')
            ->with($countries)
            ->willReturn(2);

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with('openpulse:countries:all');

        $result = $this->service->import();

        $this->assertSame(2, $result);
    }

    public function testImportPropagatesProviderException(): void
    {
        $this->countryProvider
            ->expects($this->once())
            ->method('fetch')
            ->willThrowException(
                new RuntimeException('Country API unavailable')
            );

        $this->countryRepository
            ->expects($this->never())
            ->method('saveMany');

        $this->cache
            ->expects($this->never())
            ->method('delete');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Country API unavailable');

        $this->service->import();
    }

    public function testImportDoesNotInvalidateCacheWhenSavingFails(): void
    {
        $countries = [
            [
                'code' => 'IN',
                'name' => 'India',
                'capital' => 'New Delhi',
                'region' => 'South Asia',
            ],
        ];

        $this->countryProvider
            ->expects($this->once())
            ->method('fetch')
            ->willReturn($countries);

        $this->countryRepository
            ->expects($this->once())
            ->method('saveMany')
            ->with($countries)
            ->willThrowException(
                new RuntimeException('Database write failed')
            );

        $this->cache
            ->expects($this->never())
            ->method('delete');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database write failed');

        $this->service->import();
    }
}

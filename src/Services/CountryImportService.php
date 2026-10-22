<?php

declare(strict_types=1);

namespace OpenPulse\Services;

use OpenPulse\Contracts\CountryProviderInterface;
use OpenPulse\Contracts\CountryRepositoryInterface;
use OpenPulse\Contracts\CacheInterface;

final class CountryImportService
{
    private const CACHE_KEY = 'openpulse:countries:all';

    public function __construct(
        private readonly CountryProviderInterface $countryProvider,
        private readonly CountryRepositoryInterface $countryRepository,
        private readonly CacheInterface $cache
    ) {
    }

    public function import(): int
    {
        $countries = $this->countryProvider->fetch();

        $importedCount = $this->countryRepository->saveMany(
            $countries
        );

        $this->cache->delete(self::CACHE_KEY);

        return $importedCount;
    }
}

<?php

declare(strict_types=1);

namespace OpenPulse\Controllers;

use OpenPulse\Contracts\CacheInterface;
use OpenPulse\Contracts\CountryRepositoryInterface;
use OpenPulse\Core\Request;

final class CountryController
{
    private const CACHE_KEY = 'openpulse:countries:all';

    private const CACHE_TTL = 300;

    public function __construct(
        private readonly CountryRepositoryInterface $countryRepository,
        private readonly CacheInterface $cache
    ) {
    }

    public function index(Request $request): array
    {
        $countries = $this->cache->get(self::CACHE_KEY);

        if ($countries !== null) {
            return [
                'countries' => $countries,
            ];
        }

        $countries = $this->countryRepository->findAll();

        $this->cache->set(
            self::CACHE_KEY,
            $countries,
            self::CACHE_TTL
        );

        return [
            'countries' => $countries,
        ];
    }
}

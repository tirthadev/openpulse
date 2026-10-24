<?php

declare(strict_types=1);

use OpenPulse\Contracts\CacheInterface;
use OpenPulse\Contracts\CountryProviderInterface;
use OpenPulse\Contracts\CountryRepositoryInterface;
use OpenPulse\Contracts\DataProviderInterface;
use OpenPulse\Contracts\DataRepositoryInterface;
use OpenPulse\Contracts\EventRepositoryInterface;
use OpenPulse\Contracts\OpenAlexSearchRepositoryInterface;
use OpenPulse\Contracts\OpenAlexWorkRepositoryInterface;
use OpenPulse\Contracts\OpenAlexWorksProviderInterface;
use OpenPulse\Core\RedisCache;
use OpenPulse\Repositories\MySqlCountryRepository;
use OpenPulse\Repositories\MySqlDataRepository;
use OpenPulse\Repositories\MySqlEventRepository;
use OpenPulse\Repositories\MySqlOpenAlexSearchRepository;
use OpenPulse\Repositories\MySqlOpenAlexWorkRepository;
use OpenPulse\Services\AlternativeDataProvider;
use OpenPulse\Services\OpenAlexApiProvider;
use OpenPulse\Services\WorldBankCountryProvider;

return [
    EventRepositoryInterface::class => MySqlEventRepository::class,
    DataProviderInterface::class => AlternativeDataProvider::class,
    DataRepositoryInterface::class => MySqlDataRepository::class,
    CountryProviderInterface::class => WorldBankCountryProvider::class,
    CountryRepositoryInterface::class => MySqlCountryRepository::class,
    CacheInterface::class => RedisCache::class,
    OpenAlexWorkRepositoryInterface::class => MySqlOpenAlexWorkRepository::class,
    OpenAlexWorksProviderInterface::class => OpenAlexApiProvider::class,
    OpenAlexSearchRepositoryInterface::class => MySqlOpenAlexSearchRepository::class,
];

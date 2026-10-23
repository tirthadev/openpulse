<?php

declare(strict_types=1);

use OpenPulse\Contracts\CountryProviderInterface;
use OpenPulse\Contracts\CountryRepositoryInterface;
use OpenPulse\Contracts\DataProviderInterface;
use OpenPulse\Contracts\DataRepositoryInterface;
use OpenPulse\Contracts\EventRepositoryInterface;
use OpenPulse\Repositories\MySqlCountryRepository;
use OpenPulse\Repositories\MySqlDataRepository;
use OpenPulse\Repositories\MySqlEventRepository;
use OpenPulse\Services\AlternativeDataProvider;
use OpenPulse\Services\WorldBankCountryProvider;
use OpenPulse\Contracts\CacheInterface;
use OpenPulse\Core\RedisCache;
use OpenPulse\Contracts\QueueInterface;
use OpenPulse\Repositories\MySqlQueue;

return [
    EventRepositoryInterface::class => MySqlEventRepository::class,
    DataProviderInterface::class => AlternativeDataProvider::class,
    DataRepositoryInterface::class => MySqlDataRepository::class,
    CountryProviderInterface::class => WorldBankCountryProvider::class,
    CountryRepositoryInterface::class => MySqlCountryRepository::class,
    CacheInterface::class => RedisCache::class,
];

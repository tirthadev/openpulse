<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use Predis\Client;
use Predis\ClientInterface;

final class RedisClientFactory
{
    public function create(array $config): ClientInterface
    {
        return new Client([
            'scheme' => $config['scheme'],
            'host' => $config['host'],
            'port' => $config['port'],
            'password' => $config['password'],
            'database' => $config['database'],
        ]);
    }
}
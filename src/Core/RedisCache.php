<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use OpenPulse\Contracts\CacheInterface;
use Predis\ClientInterface;
use RuntimeException;
use JsonException;

final class RedisCache implements CacheInterface
{
    public function __construct(
        private readonly ClientInterface $redis
    ) {
    }

    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);

        if ($value === null) {
            return null;
        }

        try {
            return json_decode(
                $value,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Unable to decode cached value.',
                previous: $exception
            );
        }
    }

    public function set(
        string $key,
        mixed $value,
        int $ttl = 300
    ): void {
        if ($ttl < 1) {
            throw new RuntimeException(
                'Cache TTL must be greater than zero.'
            );
        }

        $json = json_encode($value, JSON_THROW_ON_ERROR);

        $this->redis->setex($key, $ttl, $json);
    }

    public function delete(string $key): void
    {
        $this->redis->del([$key]);
    }
}

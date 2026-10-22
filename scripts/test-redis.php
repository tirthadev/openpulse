<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use OpenPulse\Core\RedisCache;
use Predis\Client;

$redis = new Client([
    'scheme' => 'tcp',
    'host' => '127.0.0.1',
    'port' => 6379,
]);

$cache = new RedisCache($redis);

$cache->set(
    'openpulse:test:countries',
    [
        ['code' => 'IN', 'name' => 'India'],
        ['code' => 'GB', 'name' => 'United Kingdom'],
    ],
    60
);

var_export($cache->get('openpulse:test:countries'));

$cache->delete('openpulse:test:countries');

echo PHP_EOL;
var_dump($cache->get('openpulse:test:countries'));

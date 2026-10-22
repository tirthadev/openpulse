<?php

declare(strict_types=1);

require_once __DIR__ . '/environment.php';

return [
    'scheme' => $_ENV['REDIS_SCHEME'] ?? 'tcp',
    'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
    'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
    'password' => $_ENV['REDIS_PASSWORD'] ?? '',
    'database' => (int) ($_ENV['REDIS_DATABASE'] ?? 0),
];
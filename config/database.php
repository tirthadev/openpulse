<?php

declare(strict_types=1);

require_once __DIR__ . '/environment.php';

$requiredVariables = [
    'DB_HOST',
    'DB_PORT',
    'DB_DATABASE',
    'DB_USERNAME',
    'DB_PASSWORD',
    'DB_CHARSET',
];

foreach ($requiredVariables as $variable) {
    if (!isset($_ENV[$variable]) || $_ENV[$variable] === '') {
        throw new RuntimeException(
            sprintf(
                'Missing required environment variable: %s',
                $variable
            )
        );
    }
}

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$database = $_ENV['DB_DATABASE'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$charset = $_ENV['DB_CHARSET'];

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $host,
    $port,
    $database,
    $charset
);

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

return new PDO($dsn, $username, $password, $options);

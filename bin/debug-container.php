<?php

declare(strict_types=1);

use OpenPulse\Core\Bootstrap;
use OpenPulse\Core\Container;
use OpenPulse\Core\Database;

require dirname(__DIR__) . '/vendor/autoload.php';

$pdo = require dirname(__DIR__) . '/config/database.php';
$database = new Database($pdo);

$container = new Container();

$container->set(PDO::class, $pdo);
$container->set(Database::class, $database);

echo "OpenPulse Container Debug\n";
echo "=========================\n";

$pdoFromContainer = $container->get(PDO::class);
$databaseFromContainer = $container->get(Database::class);

echo 'PDO instance reused: ';
echo $pdoFromContainer === $pdo ? "PASS\n" : "FAIL\n";

echo 'Database instance reused: ';
echo $databaseFromContainer === $database ? "PASS\n" : "FAIL\n";

echo 'PDO driver: ';
echo $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";

echo 'Database connection: ';

try {
    $pdo->query('SELECT 1');
    echo "PASS\n";
} catch (PDOException $exception) {
    echo "FAIL: " . $exception->getMessage() . "\n";
}
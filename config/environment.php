<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$projectRoot = dirname(__DIR__);

require_once $projectRoot . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable($projectRoot);
$dotenv->safeLoad();

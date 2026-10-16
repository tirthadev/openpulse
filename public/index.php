<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use OpenPulse\Core\Application;

$application = new Application();
print($application->run());
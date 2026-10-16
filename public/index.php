<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use OpenPulse\Core\Application;
use OpenPulse\Services\GreetingService;

try{
    $application = new Application(new GreetingService());
    print($application->run('OpenPulse'));
} catch(InvalidArgumentException $exception){
    http_response_code(400);
    echo 'Invalid input: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

<?php
declare(strict_types=1);
namespace OpenPulse\Core;

use OpenPulse\Services\GreetingService;

class Application
{
    public function run() : string {
        $greetingService = new GreetingService();
        return $greetingService->greet('OpenPulse');
    }
}
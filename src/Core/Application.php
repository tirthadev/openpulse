<?php
declare(strict_types=1);
namespace OpenPulse\Core;

use OpenPulse\Services\GreetingService;

class Application
{
    public function __construct(private readonly GreetingService $greetingService){

    }
    public function run(string $name='OpenPulse') : string {
        return $this->greetingService->greet($name);
    }
}
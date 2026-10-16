<?php
declare(strict_types=1);
namespace OpenPulse\Services;

class GreetingService
{
    public function greet(string $name) : string {
        return 'Hello, '.$name.'!';
    }
}
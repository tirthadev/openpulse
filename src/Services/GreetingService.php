<?php

declare(strict_types=1);

namespace OpenPulse\Services;

use InvalidArgumentException;

final class GreetingService
{
    public function greet(string $name): string
    {
        if (trim($name) === '') {
            throw new InvalidArgumentException('Name should not be empty!');
        }
        return 'Hello, ' . trim($name) . '!';
    }
}

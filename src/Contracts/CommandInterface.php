<?php

declare(strict_types=1);

namespace OpenPulse\Contracts;

interface CommandInterface
{
    public function execute(array $arguments = []): int;
}

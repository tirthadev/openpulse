<?php

declare(strict_types=1);

namespace OpenPulse\Services;

use InvalidArgumentException;
use OpenPulse\Contracts\DataProviderInterface;

final class AlternativeDataProvider implements DataProviderInterface
{
    public function fetch(): array
    {
        return ['tirtha', 'ankita', 'arshav', 'sangita'];
    }
}

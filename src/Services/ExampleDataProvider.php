<?php
declare(strict_types=1);
namespace OpenPulse\Services;

use InvalidArgumentException;
use OpenPulse\Contracts\DataProviderInterface;

final class ExampleDataProvider implements DataProviderInterface
{
    public function fetch() : array {
        return [1, 2, 3, 4, 5];
    }
}
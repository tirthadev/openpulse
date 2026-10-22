<?php

declare(strict_types=1);

namespace OpenPulse\Contracts;

interface CountryProviderInterface
{
    public function fetch(): array;
}

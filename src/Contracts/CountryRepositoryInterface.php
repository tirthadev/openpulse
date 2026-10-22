<?php

declare(strict_types=1);

namespace OpenPulse\Contracts;

interface CountryRepositoryInterface
{
    public function saveMany(array $countries): int;
    public function findAll(): array;
}

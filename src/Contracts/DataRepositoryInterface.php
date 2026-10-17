<?php

declare(strict_types=1);

namespace OpenPulse\Contracts;

interface DataRepositoryInterface
{
    public function save(array $data): void;
}

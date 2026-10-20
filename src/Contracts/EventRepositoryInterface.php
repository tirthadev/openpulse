<?php

declare(strict_types=1);

namespace OpenPulse\Contracts;

interface EventRepositoryInterface
{
    public function findAll(): array;
    public function save(array $data): void;
}

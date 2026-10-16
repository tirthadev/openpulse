<?php
declare(strict_types=1);

namespace OpenPulse\Repositories;

use OpenPulse\Contracts\DataRepositoryInterface;

final class InMemoryDataRepository implements DataRepositoryInterface
{
    private array $inMemoryRecords = [];

    public function save(array $data): void
    {
        $this->inMemoryRecords[] = $data;
    }

    public function getAll(): array
    {
        return $this->inMemoryRecords;
    }
}
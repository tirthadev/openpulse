<?php

declare(strict_types=1);

namespace OpenPulse\Repositories;

use OpenPulse\Contracts\DataRepositoryInterface;
use OpenPulse\Core\Database;

final class MySqlDataRepository implements DataRepositoryInterface
{
    public function __construct(private readonly Database $database)
    {
    }

    public function save(array $data): void
    {
        $data = json_encode($data, JSON_THROW_ON_ERROR);
        $connection = $this->database->getConnection();

        $query = $connection->prepare('INSERT INTO collected_data (`payload`) VALUES (:data)');
        $query->execute(['data' => $data]);
    }
}

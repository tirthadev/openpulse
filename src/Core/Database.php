<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use PDO;

final class Database
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}

<?php

declare(strict_types=1);

namespace OpenPulse\Repositories;

use OpenPulse\Contracts\CountryRepositoryInterface;
use PDO;

final class MySqlCountryRepository implements CountryRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function saveMany(array $countries): int
    {
        $sql = <<<'SQL'
            INSERT INTO countries (code, name, capital, region)
            VALUES (:code, :name, :capital, :region)
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                capital = VALUES(capital),
                region = VALUES(region)
            SQL;

        $statement = $this->pdo->prepare($sql);

        $this->pdo->beginTransaction();

        try {
            foreach ($countries as $country) {
                $statement->execute([
                    'code' => $country['code'],
                    'name' => $country['name'],
                    'capital' => $country['capital'],
                    'region' => $country['region'],
                ]);
            }

            $this->pdo->commit();

            return count($countries);
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT code, name, capital, region
            FROM countries
            ORDER BY name ASC'
        );

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}

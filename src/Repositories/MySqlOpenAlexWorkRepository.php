<?php

declare(strict_types=1);

namespace OpenPulse\Repositories;

use OpenPulse\Contracts\OpenAlexWorkRepositoryInterface;
use OpenPulse\Core\Database;
use Throwable;

final class MySqlOpenAlexWorkRepository implements OpenAlexWorkRepositoryInterface
{
    private const BATCH_SIZE = 100;

    private const COLUMNS = [
        'openalex_id',
        'doi',
        'title',
        'publication_year',
        'publication_date',
        'work_type',
        'cited_by_count',
        'is_open_access',
    ];

    public function __construct(
        private readonly Database $database,
    ) {
    }

    /**
     * @param list<array{
     *     openalex_id: string,
     *     doi: string|null,
     *     title: string|null,
     *     publication_year: int|null,
     *     publication_date: string|null,
     *     work_type: string,
     *     cited_by_count: int,
     *     is_open_access: bool|int
     * }> $works
     */
    public function upsertMany(array $works): void
    {
        if ($works === []) {
            return;
        }

        $pdo = $this->database->getConnection();
        $ownsTransaction = !$pdo->inTransaction();

        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            foreach (array_chunk($works, self::BATCH_SIZE) as $batch) {
                $this->upsertBatch($batch);
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($ownsTransaction) {
                $this->rollbackIfActive($pdo);
            }

            throw $exception;
        }
    }

    /**
     * @param list<array{
     *     openalex_id: string,
     *     doi: string|null,
     *     title: string|null,
     *     publication_year: int|null,
     *     publication_date: string|null,
     *     work_type: string,
     *     cited_by_count: int,
     *     is_open_access: bool|int
     * }> $batch
     */
    private function upsertBatch(array $batch): void
    {
        $columnList = implode(
            ', ',
            array_map(
                static fn (string $column): string => "`{$column}`",
                self::COLUMNS,
            ),
        );

        $rowPlaceholder = '(' . implode(
            ', ',
            array_fill(0, count(self::COLUMNS), '?'),
        ) . ')';

        $placeholders = implode(
            ', ',
            array_fill(0, count($batch), $rowPlaceholder),
        );

        $updates = [
            'doi',
            'title',
            'publication_year',
            'publication_date',
            'work_type',
            'cited_by_count',
            'is_open_access',
        ];

        $updateClause = implode(
            ",\n",
            array_map(
                static fn (string $column): string =>
                    "`{$column}` = incoming.`{$column}`",
                $updates,
            ),
        );

        $sql = <<<SQL
            INSERT INTO openalex_works ({$columnList})
            VALUES {$placeholders} AS incoming
            ON DUPLICATE KEY UPDATE
            {$updateClause}
            SQL;

        $parameters = [];

        foreach ($batch as $work) {
            $parameters[] = $work['openalex_id'];
            $parameters[] = $work['doi'];
            $parameters[] = $work['title'];
            $parameters[] = $work['publication_year'];
            $parameters[] = $work['publication_date'];
            $parameters[] = $work['work_type'];
            $parameters[] = $work['cited_by_count'];
            $parameters[] = (int) $work['is_open_access'];
        }

        $statement = $this->database
            ->getConnection()
            ->prepare($sql);

        $statement->execute($parameters);
    }

    private function rollbackIfActive(\PDO $pdo): void
    {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}
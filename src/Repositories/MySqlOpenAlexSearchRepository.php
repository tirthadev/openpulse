<?php

declare(strict_types=1);

namespace OpenPulse\Repositories;

use InvalidArgumentException;
use JsonException;
use OpenPulse\Contracts\OpenAlexSearchRepositoryInterface;
use PDO;
use Throwable;

final class MySqlOpenAlexSearchRepository implements OpenAlexSearchRepositoryInterface
{
    private const MAX_PER_PAGE = 100;

    /**
     * @var array<string, string>
     */
    private const SORTABLE_FIELDS = [
        'id' => 'id',
        'title' => 'title',
        'publication_year' => 'publication_year',
        'publication_date' => 'publication_date',
        'work_type' => 'work_type',
        'cited_by_count' => 'cited_by_count',
        'created_at' => 'created_at',
    ];

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /**
     * @param list<array{field: string, direction: string}> $sort
     *
     * @return array{
     *     data: list<array<string, mixed>>,
     *     per_page: int,
     *     has_more: bool,
     *     next_cursor: ?string
     * }
     */
    public function search(
        string $query = '',
        ?int $publicationYear = null,
        ?string $workType = null,
        ?bool $isOpenAccess = null,
        array $sort = [],
        ?string $cursor = null,
        int $perPage = 25,
    ): array {
        if ($perPage < 1 || $perPage > self::MAX_PER_PAGE) {
            throw new InvalidArgumentException(
                'Per-page value must be between 1 and 100.',
            );
        }

        $effectiveSort = $this->normalizeSort($sort);

        $conditions = [];
        $parameters = [];

        if (trim($query) !== '') {
            $conditions[] = 'title LIKE :title_query';
            $parameters['title_query'] = '%' . trim($query) . '%';
        }

        if ($publicationYear !== null) {
            $conditions[] = 'publication_year = :publication_year';
            $parameters['publication_year'] = $publicationYear;
        }

        if ($workType !== null && trim($workType) !== '') {
            $conditions[] = 'work_type = :work_type';
            $parameters['work_type'] = trim($workType);
        }

        if ($isOpenAccess !== null) {
            $conditions[] = 'is_open_access = :is_open_access';
            $parameters['is_open_access'] = $isOpenAccess ? 1 : 0;
        }

        if ($cursor !== null && $cursor !== '') {
            $cursorValues = $this->decodeCursor($cursor, $effectiveSort);

            $conditions[] = $this->buildCursorCondition(
                $effectiveSort,
                $cursorValues,
                $parameters,
            );
        }

        $whereSql = $conditions === []
            ? ''
            : ' WHERE ' . implode(' AND ', $conditions);

        $orderBySql = $this->buildOrderBy($effectiveSort);

        $sql = 'SELECT
                    id,
                    openalex_id,
                    doi,
                    title,
                    publication_year,
                    publication_date,
                    work_type,
                    cited_by_count,
                    is_open_access,
                    created_at,
                    updated_at
                FROM openalex_works'
            . $whereSql
            . $orderBySql
            . ' LIMIT :limit';

        $statement = $this->pdo->prepare($sql);

        foreach ($parameters as $name => $value) {
            $statement->bindValue(':' . $name, $value);
        }

        $statement->bindValue(
            ':limit',
            $perPage + 1,
            PDO::PARAM_INT,
        );

        $statement->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $hasMore = count($rows) > $perPage;

        if ($hasMore) {
            array_pop($rows);
        }

        $nextCursor = null;

        if ($hasMore) {
            $lastRow = $rows[array_key_last($rows)];
            $values = [];

            foreach ($effectiveSort as $item) {
                $values[] = $lastRow[$item['field']] ?? null;
            }

            $nextCursor = $this->encodeCursor(
                $effectiveSort,
                $values,
            );
        }

        return [
            'data' => $rows,
            'per_page' => $perPage,
            'has_more' => $hasMore,
            'next_cursor' => $nextCursor,
        ];
    }

    /**
     * Append a unique tie-breaker unless the requested sort already
     * includes the unique primary key.
     *
     * @param list<array{field: string, direction: string}> $sort
     * @return list<array{field: string, direction: string}>
     */
    private function normalizeSort(array $sort): array
    {
        if ($sort === []) {
            return [
                ['field' => 'id', 'direction' => 'DESC'],
            ];
        }

        $normalized = [];
        $seenFields = [];

        foreach ($sort as $item) {
            $field = $item['field'];
            $direction = strtoupper($item['direction']);

            if (!isset(self::SORTABLE_FIELDS[$field])) {
                throw new InvalidArgumentException(
                    sprintf('Unsupported sort field: %s.', $field),
                );
            }

            if (!in_array($direction, ['ASC', 'DESC'], true)) {
                throw new InvalidArgumentException(
                    'Sort direction must be ASC or DESC.',
                );
            }

            if (isset($seenFields[$field])) {
                throw new InvalidArgumentException(
                    sprintf('Duplicate sort field: %s.', $field),
                );
            }

            $seenFields[$field] = true;
            $normalized[] = [
                'field' => $field,
                'direction' => $direction,
            ];
        }

        if (!isset($seenFields['id'])) {
            $normalized[] = [
                'field' => 'id',
                'direction' => 'ASC',
            ];
        }

        return $normalized;
    }

    /**
     * @param list<array{field: string, direction: string}> $sort
     */
    private function buildOrderBy(array $sort): string
    {
        $clauses = [];

        foreach ($sort as $item) {
            $column = self::SORTABLE_FIELDS[$item['field']];
            $clauses[] = $column . ' ' . $item['direction'];
        }

        return ' ORDER BY ' . implode(', ', $clauses);
    }

    
    /**
     * Build a lexicographic keyset condition for all sort columns.
     *
     * MySQL sorts NULL values first in ASC order and last in DESC order.
     *
     * @param list<array{field: string, direction: string}> $sort
     * @param list<mixed> $values
     * @param array<string, mixed> $parameters
     */
    
    private function buildCursorCondition(
        array $sort,
        array $values,
        array &$parameters,
    ): string {
        $alternatives = [];
        $equalPrefix = [];
        $sortCount = count($sort);

        foreach ($sort as $index => $item) {
            $column = self::SORTABLE_FIELDS[$item['field']];
            $value = $values[$index];
            $afterParameter = 'cursor_after_' . $index;

            if ($value === null) {
                $after = $item['direction'] === 'ASC'
                    ? $column . ' IS NOT NULL'
                    : '1 = 0';
            } else {
                $parameters[$afterParameter] = $value;

                $operator = $item['direction'] === 'ASC'
                    ? '>'
                    : '<';

                $after = sprintf(
                    '(%s %s :%s',
                    $column,
                    $operator,
                    $afterParameter,
                );

                if ($item['direction'] === 'DESC') {
                    $after .= sprintf(' OR %s IS NULL', $column);
                }

                $after .= ')';
            }

            $prefix = $equalPrefix === []
                ? ''
                : implode(' AND ', $equalPrefix) . ' AND ';

            $alternatives[] = '(' . $prefix . $after . ')';

            // Only add an equality parameter when a later sort field
            // will actually use it in the SQL condition.
            if ($index < $sortCount - 1) {
                $equalParameter = 'cursor_eq_' . $index;
                $parameters[$equalParameter] = $value;

                $equalPrefix[] = sprintf(
                    '%s <=> :%s',
                    $column,
                    $equalParameter,
                );
            }
        }

        return '(' . implode(' OR ', $alternatives) . ')';
    }



    /**
     * @param list<array{field: string, direction: string}> $sort
     * @param list<mixed> $values
     */
    private function encodeCursor(array $sort, array $values): string
    {
        try {
            $json = json_encode([
                'sort' => $sort,
                'values' => $values,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException(
                'Unable to encode pagination cursor.',
                previous: $exception,
            );
        }

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    /**
     * @param list<array{field: string, direction: string}> $expectedSort
     * @return list<mixed>
     */
    private function decodeCursor(
        string $cursor,
        array $expectedSort,
    ): array {
        $base64 = strtr($cursor, '-_', '+/');
        $padding = (4 - strlen($base64) % 4) % 4;
        $json = base64_decode($base64 . str_repeat('=', $padding), true);

        if ($json === false) {
            throw new InvalidArgumentException('Invalid pagination cursor.');
        }

        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException(
                'Invalid pagination cursor.',
                previous: $exception,
            );
        }

        if (
            !is_array($payload)
            || !isset($payload['sort'], $payload['values'])
            || !is_array($payload['sort'])
            || !is_array($payload['values'])
        ) {
            throw new InvalidArgumentException('Invalid pagination cursor.');
        }

        $cursorSort = [];

        foreach ($payload['sort'] as $item) {
            if (
                !is_array($item)
                || !isset($item['field'], $item['direction'])
                || !is_string($item['field'])
                || !is_string($item['direction'])
            ) {
                throw new InvalidArgumentException(
                    'Invalid pagination cursor.',
                );
            }

            $cursorSort[] = [
                'field' => $item['field'],
                'direction' => $item['direction'],
            ];
        }

        if (
            $cursorSort !== $expectedSort
            || count($payload['values']) !== count($expectedSort)
        ) {
            throw new InvalidArgumentException(
                'Cursor does not match the requested sort order.',
            );
        }

        foreach ($payload['values'] as $value) {
            if (
                $value !== null
                && !is_string($value)
                && !is_int($value)
                && !is_float($value)
                && !is_bool($value)
            ) {
                throw new InvalidArgumentException(
                    'Invalid pagination cursor values.',
                );
            }
        }

        /** @var list<mixed> $values */
        $values = array_values($payload['values']);

        return $values;
    }
}
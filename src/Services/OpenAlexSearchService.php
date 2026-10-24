<?php

declare(strict_types=1);

namespace OpenPulse\Services;

use OpenPulse\Contracts\OpenAlexSearchRepositoryInterface;
use InvalidArgumentException;

final class OpenAlexSearchService
{
    public function __construct(
        private readonly OpenAlexSearchRepositoryInterface $repository
    ) {
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array{
     *     data: list<array<string, mixed>>,
     *     per_page: int,
     *     has_more: bool,
     *     next_cursor: ?string
     * }
     */
    public function search(array $parameters): array
    {
        $query = $parameters['q'] ?? '';

        if (!is_string($query)) {
            throw new InvalidArgumentException(
                'The q parameter must be a string.'
            );
        }

        $publicationYear = $this->parseInteger(
            $parameters['publication_year'] ?? null,
            'publication_year'
        );

        $workType = $parameters['work_type'] ?? null;

        if ($workType !== null && !is_string($workType)) {
            throw new InvalidArgumentException(
                'The work_type parameter must be a string.'
            );
        }

        $isOpenAccess = $this->parseBoolean(
            $parameters['is_open_access'] ?? null,
            'is_open_access'
        );

        $perPage = $this->parseInteger(
            $parameters['per_page'] ?? 25,
            'per_page'
        );

        if ($perPage === null || $perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException(
                'per_page must be between 1 and 100.'
            );
        }

        $cursor = $parameters['cursor'] ?? null;

        if ($cursor !== null && !is_string($cursor)) {
            throw new InvalidArgumentException(
                'The cursor parameter must be a string.'
            );
        }

        $sort = $this->parseSort($parameters['sort'] ?? null);

        return $this->repository->search(
            query: $query,
            publicationYear: $publicationYear,
            workType: $workType,
            isOpenAccess: $isOpenAccess,
            sort: $sort,
            cursor: $cursor,
            perPage: $perPage
        );
    }

    private function parseInteger(
        mixed $value,
        string $parameter
    ): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException(
                sprintf('%s must be an integer.', $parameter)
            );
        }

        return (int) $value;
    }

    private function parseBoolean(
        mixed $value,
        string $parameter
    ): ?bool {
        if ($value === null || $value === '') {
            return null;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($parsed === null) {
            throw new InvalidArgumentException(
                sprintf('%s must be true or false.', $parameter)
            );
        }

        return $parsed;
    }

    /**
     * Accepts comma-separated fields such as:
     * publication_year:desc,title:asc
     *
     * @return list<array{field: string, direction: string}>
     */
    private function parseSort(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException(
                'The sort parameter must be a string.'
            );
        }

        $sort = [];

        foreach (explode(',', $value) as $item) {
            $parts = explode(':', trim($item), 2);

            $field = trim($parts[0]);
            $direction = strtolower(trim($parts[1] ?? 'asc'));

            if ($field === '') {
                throw new InvalidArgumentException(
                    'Sort fields cannot be empty.'
                );
            }

            if (!in_array($direction, ['asc', 'desc'], true)) {
                throw new InvalidArgumentException(
                    'Sort direction must be asc or desc.'
                );
            }

            $sort[] = [
                'field' => $field,
                'direction' => $direction,
            ];
        }

        return $sort;
    }
}
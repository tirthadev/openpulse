<?php

declare(strict_types=1);

namespace OpenPulse\Contracts;

interface OpenAlexSearchRepositoryInterface
{
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
    ): array;
}

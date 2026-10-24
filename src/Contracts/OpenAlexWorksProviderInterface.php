<?php

declare(strict_types=1);

namespace OpenPulse\Contracts;

interface OpenAlexWorksProviderInterface
{
    /**
     * Fetch one page of works from OpenAlex.
     *
     * @return array{
     *     works: list<array{
     *         openalex_id: string,
     *         doi: string|null,
     *         title: string|null,
     *         publication_year: int|null,
     *         publication_date: string|null,
     *         work_type: string,
     *         cited_by_count: int,
     *         is_open_access: bool
     *     }>,
     *     next_cursor: string|null
     * }
     */
    public function fetchPage(
        ?string $cursor = null,
        int $perPage = 100
    ): array;
}
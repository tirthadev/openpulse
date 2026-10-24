<?php

declare(strict_types=1);

namespace OpenPulse\Contracts;

interface OpenAlexWorkRepositoryInterface
{
    /**
     * Insert new works or update existing works by OpenAlex ID.
     *
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
    public function upsertMany(array $works): void;
}
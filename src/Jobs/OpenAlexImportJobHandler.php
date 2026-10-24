<?php

declare(strict_types=1);

namespace OpenPulse\Jobs;

use InvalidArgumentException;
use OpenPulse\Contracts\JobHandlerInterface;
use OpenPulse\Contracts\OpenAlexWorkRepositoryInterface;
use OpenPulse\Contracts\OpenAlexWorksProviderInterface;
use OpenPulse\Contracts\QueueInterface;

final class OpenAlexImportJobHandler implements JobHandlerInterface
{
    private const MAX_PAGE_SIZE = 100;

    private const MAX_TARGET_RECORDS = 500_000;

    public function __construct(
        private readonly OpenAlexWorksProviderInterface $provider,
        private readonly OpenAlexWorkRepositoryInterface $repository,
        private readonly QueueInterface $queue,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload): void
    {
        $cursor = $payload['cursor'] ?? null;
        $perPage = $payload['per_page'] ?? self::MAX_PAGE_SIZE;
        $recordsRemaining = $payload['records_remaining'] ?? null;

        if ($cursor !== null && !is_string($cursor)) {
            throw new InvalidArgumentException(
                'OpenAlex cursor must be a string or null.',
            );
        }

        if (
            !is_int($perPage)
            || $perPage < 1
            || $perPage > self::MAX_PAGE_SIZE
        ) {
            throw new InvalidArgumentException(
                'OpenAlex per_page must be between 1 and 100.',
            );
        }

        if (
            !is_int($recordsRemaining)
            || $recordsRemaining < 1
            || $recordsRemaining > self::MAX_TARGET_RECORDS
        ) {
            throw new InvalidArgumentException(
                'OpenAlex records_remaining must be between 1 and 500000.',
            );
        }

        // Request no more than the remaining target.
        $requestedCount = min($perPage, $recordsRemaining);

        $page = $this->provider->fetchPage(
            $cursor,
            $requestedCount,
        );

        $works = $page['works'];
        $nextCursor = $page['next_cursor'];

        if (count($works) > $requestedCount) {
            throw new \RuntimeException(
                'OpenAlex returned more works than requested.',
            );
        }

        if ($works !== []) {
            $this->repository->upsertMany($works);
        }

        $recordsRemaining -= count($works);

        if ($recordsRemaining === 0 || $nextCursor === null) {
            return;
        }

        // Stop instead of endlessly scheduling empty pages.
        if ($works === []) {
            throw new \RuntimeException(
                'OpenAlex returned an empty page with a next cursor.',
            );
        }

        $this->queue->push(
            'openalex.import',
            [
                'cursor' => $nextCursor,
                'per_page' => $perPage,
                'records_remaining' => $recordsRemaining,
            ],
        );
    }
}

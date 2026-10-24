<?php

declare(strict_types=1);

namespace OpenPulse\Commands;

use OpenPulse\Contracts\CommandInterface;
use OpenPulse\Contracts\QueueInterface;

final class QueuePushCommand implements CommandInterface
{
    private const DEFAULT_TARGET_RECORDS = 500_000;

    private const MAX_TARGET_RECORDS = 500_000;

    private const DEFAULT_PER_PAGE = 100;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private readonly QueueInterface $queue,
    ) {
    }

    /**
     * @param list<string> $arguments
     */
    public function execute(array $arguments = []): int
    {
        if ($arguments === []) {
            $this->printUsage();

            return 1;
        }

        $jobType = $arguments[0];

        if ($jobType === 'country.import') {
            if (count($arguments) !== 1) {
                $this->printUsage();

                return 1;
            }

            $jobId = $this->queue->push('country.import', []);

            fwrite(STDOUT, sprintf("Queued job with ID: %d\n", $jobId));

            return 0;
        }

        if ($jobType === 'openalex.import') {
            return $this->queueOpenAlexImport(array_slice($arguments, 1));
        }

        $this->printUsage();

        return 1;
    }

    /**
     * @param list<string> $arguments
     */
    private function queueOpenAlexImport(array $arguments): int
    {
        if (count($arguments) > 3) {
            $this->printUsage();

            return 1;
        }

        $targetRecords = self::DEFAULT_TARGET_RECORDS;
        $perPage = self::DEFAULT_PER_PAGE;
        $cursor = null;

        if (isset($arguments[0])) {
            $targetRecords = filter_var($arguments[0], FILTER_VALIDATE_INT);

            if (
                !is_int($targetRecords)
                || $targetRecords < 1
                || $targetRecords > self::MAX_TARGET_RECORDS
            ) {
                fwrite(STDERR, "Target records must be between 1 and 500000.\n");

                return 1;
            }
        }

        if (isset($arguments[1])) {
            $perPage = filter_var($arguments[1], FILTER_VALIDATE_INT);

            if (
                !is_int($perPage)
                || $perPage < 1
                || $perPage > self::MAX_PER_PAGE
            ) {
                fwrite(STDERR, "Records per page must be between 1 and 100.\n");

                return 1;
            }
        }

        if (isset($arguments[2])) {
            $cursor = $arguments[2];

            if ($cursor === '') {
                fwrite(STDERR, "Cursor cannot be empty when provided.\n");

                return 1;
            }
        }

        $jobId = $this->queue->push(
            'openalex.import',
            [
                'cursor' => $cursor,
                'per_page' => $perPage,
                'records_remaining' => $targetRecords,
            ],
        );

        fwrite(
            STDOUT,
            sprintf(
                "Queued OpenAlex import job %d (target: %d works, up to %d records per page).\n",
                $jobId,
                $targetRecords,
                $perPage,
            ),
        );

        return 0;
    }

    private function printUsage(): void
    {
        fwrite(
            STDERR,
            "Usage:\n"
            . "  php bin/openpulse queue:push country.import\n"
            . "  php bin/openpulse queue:push openalex.import [target-records] [per-page] [cursor]\n",
        );
    }
}

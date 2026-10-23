<?php

declare(strict_types=1);

namespace OpenPulse\Commands;

use OpenPulse\Contracts\CommandInterface;
use OpenPulse\Core\Profiler;
use PDO;

final class ProfileCommand implements CommandInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly Profiler $profiler,
    ) {
    }

    /**
     * @param list<string> $arguments
     */
    public function execute(array $arguments = []): int
    {
        $metrics = $this->profiler->profile(
            fn (): int => (int) $this->pdo
                ->query('SELECT COUNT(*) FROM countries')
                ->fetchColumn()
        );

        printf("Countries count: %d\n", $metrics['result']);

        printf(
            "Execution time: %.3f ms\n",
            $metrics['duration_ms']
        );

        printf(
            "Memory before: %.2f MB\n",
            $metrics['memory_before_bytes'] / 1024 / 1024
        );

        printf(
            "Memory after: %.2f MB\n",
            $metrics['memory_after_bytes'] / 1024 / 1024
        );

        printf(
            "Memory delta: %+.2f MB\n",
            $metrics['memory_delta_bytes'] / 1024 / 1024
        );

        printf(
            "Peak memory: %.2f MB\n",
            $metrics['peak_memory_bytes'] / 1024 / 1024
        );

        return 0;
    }
}

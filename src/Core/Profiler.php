<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use Throwable;

final class Profiler
{
    /**
     * @template T
     *
     * @param callable(): T $operation
     * @return array{
     *     result: T,
     *     duration_ms: float,
     *     memory_before_bytes: int,
     *     memory_after_bytes: int,
     *     memory_delta_bytes: int,
     *     peak_memory_bytes: int
     * }
     */
    public function profile(callable $operation): array
    {
        $memoryBefore = memory_get_usage(true);
        $peakMemoryBefore = memory_get_peak_usage(true);
        $startTime = hrtime(true);

        try {
            $result = $operation();
        } finally {
            $endTime = hrtime(true);
            $memoryAfter = memory_get_usage(true);
            $peakMemoryAfter = memory_get_peak_usage(true);
        }

        return [
            'result' => $result,
            'duration_ms' => ($endTime - $startTime) / 1_000_000,
            'memory_before_bytes' => $memoryBefore,
            'memory_after_bytes' => $memoryAfter,
            'memory_delta_bytes' => $memoryAfter - $memoryBefore,
            'peak_memory_bytes' => max(
                $peakMemoryBefore,
                $peakMemoryAfter
            ),
        ];
    }
}

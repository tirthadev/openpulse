<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use InvalidArgumentException;

final class QueueConfig
{
    public function __construct(
        public readonly int $maxAttempts = 3,
        public readonly int $retryDelaySeconds = 30,
        public readonly int $reservationTimeoutSeconds = 300,
    ) {
        if ($this->maxAttempts < 1) {
            throw new InvalidArgumentException(
                'Queue max attempts must be at least one.'
            );
        }

        if ($this->retryDelaySeconds < 0) {
            throw new InvalidArgumentException(
                'Queue retry delay cannot be negative.'
            );
        }

        if ($this->reservationTimeoutSeconds < 1) {
            throw new InvalidArgumentException(
                'Queue reservation timeout must be at least one second.'
            );
        }
    }

    public static function fromEnvironment(): self
    {
        return new self(
            maxAttempts: self::readInteger('QUEUE_MAX_ATTEMPTS', 3, 1),
            retryDelaySeconds: self::readInteger(
                'QUEUE_RETRY_DELAY_SECONDS',
                30,
                0
            ),
            reservationTimeoutSeconds: self::readInteger(
                'QUEUE_RESERVATION_TIMEOUT_SECONDS',
                300,
                1
            ),
        );
    }

    private static function readInteger(
        string $name,
        int $default,
        int $minimum
    ): int {
        $value = $_ENV[$name] ?? (string) $default;

        if (
            !is_string($value)
            || filter_var($value, FILTER_VALIDATE_INT) === false
        ) {
            throw new InvalidArgumentException(
                sprintf('Environment variable %s must be an integer.', $name)
            );
        }

        $integer = (int) $value;

        if ($integer < $minimum) {
            throw new InvalidArgumentException(
                sprintf(
                    'Environment variable %s must be at least %d.',
                    $name,
                    $minimum
                )
            );
        }

        return $integer;
    }
}

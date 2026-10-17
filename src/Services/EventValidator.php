<?php

declare(strict_types=1);

namespace OpenPulse\Services;

final class EventValidator
{
    public function validate(array $data): void
    {
        if (!array_key_exists('event', $data)) {
            throw new \InvalidArgumentException(
                'Data must have an event property.'
            );
        }
        if (!is_string($data['event'])) {
            throw new \InvalidArgumentException(
                'The event field must be a string.'
            );
        }
        if (trim($data['event']) === '') {
             throw new \InvalidArgumentException(
                 'The event field must be a non-empty string.'
             );
        }
    }
}

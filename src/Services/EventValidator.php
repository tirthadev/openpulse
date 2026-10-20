<?php

declare(strict_types=1);

namespace OpenPulse\Services;

final class EventValidator
{
    public function validate(array $data): array
    {
        // Validate the required event field.
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

        // Normalize the event name before validating it.
        $data['event'] = trim($data['event']);

        if ($data['event'] === '') {
            throw new \InvalidArgumentException(
                'The event field must be a non-empty string.'
            );
        }

        // Validate and normalize the optional user_id.
        if (
            array_key_exists('user_id', $data)
            && $data['user_id'] !== null
        ) {
            if (!is_string($data['user_id'])) {
                throw new \InvalidArgumentException(
                    'The user_id field must be a string or null.'
                );
            }

            $data['user_id'] = trim($data['user_id']);

            if ($data['user_id'] === '') {
                throw new \InvalidArgumentException(
                    'The user_id field must not be empty.'
                );
            }
        }

        // Validate the optional properties field.
        if (
            array_key_exists('properties', $data)
            && $data['properties'] !== null
            && !is_array($data['properties'])
        ) {
            throw new \InvalidArgumentException(
                'The properties field must be an array or null.'
            );
        }

        return $data;
    }
}

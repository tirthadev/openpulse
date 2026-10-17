<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use JsonException;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $body = []
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $path = parse_url(
            $_SERVER['REQUEST_URI'] ?? '/',
            PHP_URL_PATH
        ) ?: '/';

        $rawBody = file_get_contents('php://input');

        $body = [];

        if ($rawBody !== false && trim($rawBody) !== '') {
            $decoded = json_decode(
                $rawBody,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (!is_array($decoded)) {
                throw new JsonException(
                    'The JSON request body must be an object or array.'
                );
            }

            $body = $decoded;
        }

        return new self($method, $path, $body);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getBody(): array
    {
        return $this->body;
    }
}

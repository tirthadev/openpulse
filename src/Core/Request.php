<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use JsonException;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $body = [],
        private readonly array $queryParams = []
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

        $queryString = parse_url(
            $requestUri,
            PHP_URL_QUERY
        );

        $queryParams = [];

        if (is_string($queryString) && $queryString !== '') {
            parse_str($queryString, $queryParams);
        }

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

        return new self(
            $method,
            $path,
            $body,
            $queryParams
        );
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

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }
}
<?php

declare(strict_types=1);

namespace OpenPulse\Core;

final class Response
{
    private function __construct(
        private readonly string $body,
        private readonly int $statusCode,
        private readonly string $contentType
    ) {
    }

    public static function json(
        array $body,
        int $statusCode = 200
    ): self {
        return new self(
            json_encode($body, JSON_THROW_ON_ERROR),
            $statusCode,
            'application/json; charset=utf-8'
        );
    }

    public static function html(
        string $body,
        int $statusCode = 200
    ): self {
        return new self(
            $body,
            $statusCode,
            'text/html; charset=utf-8'
        );
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        header('Content-Type: ' . $this->contentType);
        echo $this->body;
    }
}
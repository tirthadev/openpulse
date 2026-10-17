<?php

declare(strict_types=1);

namespace OpenPulse\Core;

final class Response
{
    public function __construct(
        private readonly array $body,
        private readonly int $statusCode = 200
    ) {
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->body, JSON_THROW_ON_ERROR);
    }
}

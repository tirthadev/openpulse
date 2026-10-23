<?php

declare(strict_types=1);

namespace OpenPulse\Jobs;

use OpenPulse\Contracts\JobHandlerInterface;
use OpenPulse\Services\CountryImportService;

final class CountryImportJobHandler implements JobHandlerInterface
{
    public function __construct(
        private readonly CountryImportService $countryImportService,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload): void
    {
        $this->countryImportService->import();
    }
}
<?php

declare(strict_types=1);

namespace OpenPulse\Commands;

use OpenPulse\Contracts\CommandInterface;
use OpenPulse\Services\CountryImportService;

final class ImportCountriesCommand implements CommandInterface
{
    public function __construct(
        private readonly CountryImportService $countryImportService
    ) {
    }

    public function execute(array $arguments = []): int
    {
        $saved = $this->countryImportService->import();

        fwrite(
            STDOUT,
            "Country import completed successfully.\n"
        );

        fwrite(
            STDOUT,
            sprintf("Records processed: %d\n", $saved)
        );

        return 0;
    }
}

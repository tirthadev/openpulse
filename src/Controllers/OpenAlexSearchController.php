<?php

declare(strict_types=1);

namespace OpenPulse\Controllers;

use InvalidArgumentException;
use OpenPulse\Core\Request;
use OpenPulse\Services\OpenAlexSearchService;

final class OpenAlexSearchController
{
    public function __construct(
        private readonly OpenAlexSearchService $searchService
    ) {
    }

    public function index(Request $request): array
    {
        try {
            return $this->searchService->search(
                $request->getQueryParams()
            );
        } catch (InvalidArgumentException $exception) {
            return [
                'error' => $exception->getMessage(),
            ];
        }
    }
}
<?php
declare(strict_types=1);

namespace OpenPulse\Core;

use OpenPulse\Contracts\DataCollectionInterface;

class Application
{
    public function __construct(private readonly DataCollectionInterface $dataCollectionService){}

    public function run() : array {
        return $this->dataCollectionService->collect();
    }
}
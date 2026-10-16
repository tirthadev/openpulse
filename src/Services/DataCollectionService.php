<?php
declare(strict_types=1);

namespace OpenPulse\Services;

use OpenPulse\Contracts\DataProviderInterface;

final class DataCollectionService
{
    public function __construct(private readonly DataProviderInterface $dataProvider){}

    public function collect() : array 
    {
        return $this->dataProvider->fetch();
    }
}
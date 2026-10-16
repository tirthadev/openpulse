<?php
declare(strict_types=1);

namespace OpenPulse\Services;

use OpenPulse\Contracts\DataProviderInterface;
use OpenPulse\Contracts\DataRepositoryInterface;

final class DataCollectionService
{
    public function __construct(private readonly DataProviderInterface $dataProvider, private readonly DataRepositoryInterface $dataRepository){}

    public function collect(): array 
    {
        $data = $this->dataProvider->fetch();
        $this->dataRepository->save( $data);
        return $data;
    }
}
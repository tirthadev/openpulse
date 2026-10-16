<?php
declare(strict_types=1);
namespace OpenPulse\Core;

use OpenPulse\Services\GreetingService;
use OpenPulse\Contracts\DataProviderInterface;

class Application
{
    public function __construct(private readonly DataProviderInterface $dataProvider){}

    public function run() : array {
        return $this->dataProvider->fetch();
    }
}
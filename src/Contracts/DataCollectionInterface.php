<?php
declare(strict_types = 1);
namespace OpenPulse\Contracts;

interface DataCollectionInterface
{
    public function collect(): array;
}
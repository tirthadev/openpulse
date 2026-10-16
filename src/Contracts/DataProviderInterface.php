<?php
declare(strict_types=1);
namespace OpenPulse\Contracts;

interface DataProviderInterface
{
    public function fetch() : array;
}
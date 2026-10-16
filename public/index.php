<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use OpenPulse\Core\Application;
use OpenPulse\Services\GreetingService;
use OpenPulse\Services\ExampleDataProvider;
use OpenPulse\Services\AlternativeDataProvider;
use OpenPulse\Services\DataCollectionService;
use OpenPulse\Repositories\InMemoryDataRepository;

try{
    //$application = new Application(new AlternativeDataProvider());
    $inMemoryDataRepository = new InMemoryDataRepository();
    $dataCollectionService = new DataCollectionService(new AlternativeDataProvider(), $inMemoryDataRepository);
    $dataCollectionService->collect();
    header('Content-Type: application/json; charset=utf-8');
    //echo json_encode($application->run(), JSON_THROW_ON_ERROR);
    
    //$inMemoryDataRepository->save($dataCollectionService->collect());
    echo json_encode($inMemoryDataRepository->getAll(), JSON_THROW_ON_ERROR);
} catch(InvalidArgumentException $exception){
    http_response_code(400);
    echo 'Invalid input: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

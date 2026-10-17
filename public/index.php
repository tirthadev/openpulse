<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

if (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === '/favicon.ico') {
    http_response_code(204);
    exit;
}

try {
    $app = OpenPulse\Core\Bootstrap::createApplication();
    $request = OpenPulse\Core\Request::fromGlobals();
    $result = $app->run($request);
    $result->send();
} catch (JsonException $exception) {
    error_log((string) $exception);
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Bad Request']);
} catch (InvalidArgumentException $exception) {
    error_log((string) $exception);
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
                        'error' => $exception->getMessage(),
                    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Internal server error']);
}

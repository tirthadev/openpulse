<?php

declare(strict_types=1);

namespace OpenPulse\Tests\Unit;

use OpenPulse\Core\Application;
use OpenPulse\Core\Bootstrap;
use OpenPulse\Core\Request;
use OpenPulse\Core\Router;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ApplicationExceptionTest extends TestCase
{
    public function testRunPropagatesRouterException(): void
    {
        $router = new Router();

        $container = Bootstrap::createContainer();

        $application = new Application($router, $container);

        $router->get(
            '/test',
            static function (Request $request): array {
                throw new RuntimeException('Request dispatch failed');
            }
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request dispatch failed');

        $application->run(new Request('GET', '/test'));
    }
}

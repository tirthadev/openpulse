<?php

declare(strict_types=1);

namespace OpenPulse\Tests\Unit;

use OpenPulse\Core\Application;
use OpenPulse\Core\Bootstrap;
use OpenPulse\Core\Request;
use OpenPulse\Core\Response;
use OpenPulse\Core\Router;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    public function testRunReturnsRouterResponse(): void
    {
        $router = new Router();

        $container = Bootstrap::createContainer();

        $application = new Application($router, $container);

        $expectedResponse = new Response(
            ['message' => 'Application is running'],
            200
        );

        $router->get(
            '/test',
            static fn (Request $request): array => [
                'message' => 'Application is running',
            ]
        );

        $result = $application->run(
            new Request('GET', '/test')
        );

        $this->assertInstanceOf(Response::class, $result);
    }
}

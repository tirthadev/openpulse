<?php

declare(strict_types=1);

namespace OpenPulse\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler !== null) {
            $data = $handler();

            return new Response($data, 200);
        }

        // Check whether the path exists for another HTTP method.
        foreach ($this->routes as $routes) {
            if (array_key_exists($path, $routes)) {
                return new Response(
                    ['error' => 'Method not allowed'],
                    405
                );
            }
        }

        return new Response(
            ['error' => 'Route not found'],
            404
        );
    }
}

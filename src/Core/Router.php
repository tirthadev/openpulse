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
            $result = $handler($request);

            if ($result instanceof Response) {
                return $result;
            }

            if (!is_array($result)) {
                throw new \UnexpectedValueException(
                    'Route handlers must return an array or a Response.'
                );
            }

            return Response::json($result);
        }

        foreach ($this->routes as $routes) {
            if (array_key_exists($path, $routes)) {
                return Response::json(
                    ['error' => 'Method not allowed'],
                    405
                );
            }
        }

        return Response::json(
            ['error' => 'Route not found'],
            404
        );
    }
}
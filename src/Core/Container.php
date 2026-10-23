<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

final class Container
{
    /**
     * Interface-to-implementation mappings.
     *
     * @var array<string, class-string>
     */
    private array $bindings = [];

    /**
     * Lazy service factories.
     *
     * @var array<string, Closure(): object>
     */
    private array $factories = [];

    /**
     * Explicitly registered or resolved instances.
     *
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * Classes currently being resolved.
     *
     * @var array<string, true>
     */
    private array $resolving = [];

    /**
     * Register an interface-to-implementation mapping.
     *
     * @param class-string $id
     * @param class-string $concrete
     */
    public function bind(string $id, string $concrete): void
    {
        if (!interface_exists($id) && !class_exists($id)) {
            throw new RuntimeException(
                sprintf('Binding target "%s" does not exist.', $id)
            );
        }

        if (!class_exists($concrete)) {
            throw new RuntimeException(
                sprintf('Concrete class "%s" does not exist.', $concrete)
            );
        }

        if (!is_a($concrete, $id, true)) {
            throw new RuntimeException(
                sprintf(
                    'Class "%s" does not implement or extend "%s".',
                    $concrete,
                    $id
                )
            );
        }

        $this->bindings[$id] = $concrete;

        unset($this->factories[$id], $this->instances[$id]);
    }

    /**
     * Register a lazy service factory.
     *
     * @param class-string $id
     * @param Closure(): object $factory
     */
    public function factory(string $id, Closure $factory): void
    {
        if (!interface_exists($id) && !class_exists($id)) {
            throw new RuntimeException(
                sprintf('Factory target "%s" does not exist.', $id)
            );
        }

        $this->factories[$id] = $factory;

        unset($this->bindings[$id], $this->instances[$id]);
    }

    /**
     * Register an existing instance.
     */
    public function set(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /**
     * Retrieve a registered instance or resolve a class automatically.
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        return $this->resolve($id);
    }

    /**
     * Construct a class and resolve its dependencies.
     */
    private function resolve(string $id): object
    {
        if (isset($this->resolving[$id])) {
            $chain = implode(' -> ', [
                ...array_keys($this->resolving),
                $id,
            ]);

            throw new RuntimeException(
                sprintf('Circular dependency detected: %s', $chain)
            );
        }

        if (!class_exists($id) && !interface_exists($id)) {
            throw new RuntimeException(
                sprintf(
                    'Class or service "%s" does not exist or is not registered.',
                    $id
                )
            );
        }

        $this->resolving[$id] = true;

        try {
            if (isset($this->factories[$id])) {
                $instance = ($this->factories[$id])();

                $this->set($id, $instance);

                return $instance;
            }

            if (isset($this->bindings[$id])) {
                $instance = $this->get($this->bindings[$id]);

                $this->set($id, $instance);

                return $instance;
            }

            if (interface_exists($id)) {
                throw new RuntimeException(
                    sprintf(
                        'Interface "%s" has no registered binding.',
                        $id
                    )
                );
            }

            $reflection = new ReflectionClass($id);

            if (!$reflection->isInstantiable()) {
                throw new RuntimeException(
                    sprintf('Class "%s" is not instantiable.', $id)
                );
            }

            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                $instance = $reflection->newInstance();
            } else {
                $dependencies = [];

                foreach ($constructor->getParameters() as $parameter) {
                    $dependencies[] = $this->resolveParameter($parameter);
                }

                $instance = $reflection->newInstanceArgs($dependencies);
            }

            $this->set($id, $instance);

            return $instance;
        } finally {
            unset($this->resolving[$id]);
        }
    }

    /**
     * Resolve a constructor parameter from its class type.
     */
    private function resolveParameter(
        ReflectionParameter $parameter
    ): object {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType) {
            throw new RuntimeException(
                sprintf(
                    'Unsupported type for parameter "$%s".',
                    $parameter->getName()
                )
            );
        }

        if ($type->isBuiltin()) {
            throw new RuntimeException(
                sprintf(
                    'Cannot automatically resolve built-in type for parameter "$%s".',
                    $parameter->getName()
                )
            );
        }

        return $this->get($type->getName());
    }
}

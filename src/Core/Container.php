<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

final class Container
{
    /**
     * Explicitly registered instances.
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
     * Register an existing instance.
     */
    public function set(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /**
     * Retrieve an instance or resolve a class automatically.
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

        if (!class_exists($id)) {
            throw new RuntimeException(
                sprintf(
                    'Class or service "%s" does not exist or is not registered.',
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

        $this->resolving[$id] = true;

        try {
            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                $newInstance = $reflection->newInstance();
            } else {
                $dependencies = [];

                foreach ($constructor->getParameters() as $parameter) {
                    $dependencies[] = $this->resolveParameter($parameter);
                }

                $newInstance = $reflection->newInstanceArgs($dependencies);
            }
            $this->set($id, $newInstance);
            return $newInstance;
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

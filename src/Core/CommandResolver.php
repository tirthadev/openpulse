<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use OpenPulse\Contracts\CommandInterface;
use RuntimeException;

final class CommandResolver
{
    /**
     * @param array<string, class-string|callable> $commands
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $commands
    ) {
    }

    public function resolve(string $name): CommandInterface
    {
        if (!isset($this->commands[$name])) {
            throw new RuntimeException(
                sprintf('Unknown command "%s".', $name)
            );
        }

        $definition = $this->commands[$name];

        $command = is_callable($definition)
            ? $definition($this->container)
            : $this->container->get($definition);

        if (!$command instanceof CommandInterface) {
            throw new RuntimeException(
                sprintf('Command "%s" must implement CommandInterface.', $name)
            );
        }

        return $command;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->commands);
    }
}
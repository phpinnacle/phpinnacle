<?php

namespace PHPinnacle\Eidos\JsonSchema;

/** @internal */
final class Node
{
    /** @param array<string, mixed> $definition */
    public function __construct(
        private readonly array $definition,
        private readonly ?string $propertyName = null,
    ) {}

    public function propertyName(): ?string
    {
        return $this->propertyName;
    }

    public function iconName(): ?string
    {
        return $this->definition['x-icon'] ?? null;
    }

    /** @return iterable<self> */
    public function nodes(): iterable
    {
        yield $this;

        foreach ($this->definition['properties'] ?? [] as $name => $property) {
            yield from new self($property, (string) $name)->nodes();
        }

        foreach ($this->definition['anyOf'] ?? [] as $variant) {
            yield from new self($variant)->nodes();
        }

        if (array_key_exists('items', $this->definition)) {
            yield from new self($this->definition['items'])->nodes();
        }
    }

    /** @param list<string> $path */
    public function hasPath(array $path): bool
    {
        if ($path === []) {
            return true;
        }

        foreach ($this->definition['anyOf'] ?? [] as $variant) {
            if (new self($variant)->hasPath($path)) {
                return true;
            }
        }

        $properties = $this->definition['properties'] ?? [];

        return (
            array_key_exists($path[0], $properties) && new self($properties[$path[0]])->hasPath(array_slice($path, 1))
        );
    }
}

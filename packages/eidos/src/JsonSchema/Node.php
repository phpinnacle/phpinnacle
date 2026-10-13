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
        return $this->hasPathAt($path, 0);
    }

    /** @param list<string> $path */
    private function hasPathAt(array $path, int $offset): bool
    {
        if ($offset === count($path)) {
            return true;
        }

        foreach ($this->definition['anyOf'] ?? [] as $variant) {
            if (new self($variant)->hasPathAt($path, $offset)) {
                return true;
            }
        }

        $properties = $this->definition['properties'] ?? [];
        $name = $path[$offset];

        return array_key_exists($name, $properties) && new self($properties[$name])->hasPathAt($path, $offset + 1);
    }
}

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

        foreach ($this->variants() as $variant) {
            yield from $variant->nodes();
        }

        if (array_key_exists('items', $this->definition)) {
            yield from new self($this->definition['items'])->nodes();
        }
    }

    public function hasPath(string $path, bool $selectArrayItemFields = false): bool
    {
        return $this->hasPathAt($path, '$', $selectArrayItemFields);
    }

    private function hasPathAt(string $path, string $prefix, bool $selectArrayItemFields): bool
    {
        if ($path === $prefix) {
            return true;
        }

        foreach ($this->variants() as $variant) {
            if ($variant->hasPathAt($path, $prefix, $selectArrayItemFields)) {
                return true;
            }
        }

        foreach ($this->definition['properties'] ?? [] as $name => $property) {
            $child = $prefix . JsonPath::property((string) $name);

            if (
                str_starts_with($path, $child)
                && new self($property)->hasPathAt($path, $child, $selectArrayItemFields)
            ) {
                return true;
            }
        }

        return (
            $selectArrayItemFields
            && array_key_exists('items', $this->definition)
            && str_starts_with($path, $prefix . '[*]')
            && new self($this->definition['items'])->hasPathAt($path, $prefix . '[*]', $selectArrayItemFields)
        );
    }

    /** @return iterable<self> */
    private function variants(): iterable
    {
        foreach (['anyOf', 'oneOf', 'allOf'] as $keyword) {
            foreach ($this->definition[$keyword] ?? [] as $variant) {
                yield new self($variant);
            }
        }
    }
}

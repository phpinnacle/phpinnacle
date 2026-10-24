<?php

namespace PHPinnacle\Jason;

use InvalidArgumentException;
use stdClass;

/** @internal */
final class References
{
    private int $nodes = 0;

    private stdClass|bool|null $referenceRoot = null;

    public function __construct(
        private readonly Schema $document,
    ) {}

    public function resolve(): Schema
    {
        return Schema::fromArray($this->expand($this->document->toArray(), root: true));
    }

    /**
     * @param array<string, mixed>|bool $schema
     * @param list<string> $references
     * @return array<string, mixed>|bool
     */
    private function expand(
        array|bool $schema,
        array $references = [],
        bool $root = false,
        bool $embeddedResource = false,
    ): array|bool {
        if (++$this->nodes > 20_000) {
            throw new InvalidArgumentException('The JSON Schema expands beyond 20000 fragments.');
        }
        if (is_bool($schema)) {
            return $schema;
        }
        $embeddedResource = $embeddedResource || !$root && array_key_exists('$id', $schema);
        if (array_key_exists('$ref', $schema)) {
            /** @var string $reference */
            $reference = $schema['$ref'];
            if (in_array($reference, $references, true)) {
                throw new InvalidArgumentException('A recursive reference cannot be expanded: ' . $reference);
            }
            if ($embeddedResource) {
                throw new InvalidArgumentException('References inside embedded $id resources are not supported.');
            }
            $resolved = $this->expand($this->target($reference), [...$references, $reference]);
            unset($schema['$ref']);
            $siblings = $schema;
            unset($siblings['$defs'], $siblings['definitions']);
            if ($siblings === []) {
                return $resolved;
            }

            return ['allOf' => [
                $resolved,
                $this->expand($schema, $references, root: $root, embeddedResource: $embeddedResource),
            ]];
        }
        foreach ($schema as $keyword => $value) {
            if (in_array($keyword, Codec::MAPS, true)) {
                /** @var array<string, array<string, mixed>|bool> $value */
                $schema[$keyword] = array_map(fn ($child) => $this->expand(
                    $child,
                    $references,
                    embeddedResource: $embeddedResource,
                ), $value);
            } elseif (in_array($keyword, Codec::LISTS, true)) {
                /** @var list<array<string, mixed>|bool> $value */
                $schema[$keyword] = array_map(fn ($child) => $this->expand(
                    $child,
                    $references,
                    embeddedResource: $embeddedResource,
                ), $value);
            } elseif (in_array($keyword, Codec::SCHEMAS, true)) {
                /** @var array<string, mixed>|bool $value */
                $schema[$keyword] = $this->expand($value, $references, embeddedResource: $embeddedResource);
            }
        }

        return $schema;
    }

    /** @return array<string, mixed>|bool */
    private function target(string $reference): array|bool
    {
        $pointer = rawurldecode(substr($reference, 1));
        if (!str_starts_with($reference, '#') || $pointer !== '' && !str_starts_with($pointer, '/')) {
            throw new InvalidArgumentException('Only local JSON Pointer references are supported: ' . $reference);
        }
        $this->referenceRoot ??= $this->document->jsonSerialize();
        $target = $this->referenceRoot;
        if ($pointer !== '') {
            foreach (explode('/', substr($pointer, 1)) as $part) {
                if (preg_match('/~(?:[^01]|$)/', $part)) {
                    throw new InvalidArgumentException('Invalid JSON Pointer escape: ' . $reference);
                }
                $part = str_replace(['~1', '~0'], ['/', '~'], $part);
                if ($target instanceof stdClass && property_exists($target, $part)) {
                    $target = $target->{$part};
                } elseif (is_array($target) && array_key_exists($part, $target)) {
                    $target = $target[$part];
                } else {
                    throw new InvalidArgumentException('The JSON Schema reference does not exist: ' . $reference);
                }
            }
        }
        if (!$target instanceof stdClass && !is_bool($target)) {
            throw new InvalidArgumentException('The reference target must be a schema: ' . $reference);
        }

        return Codec::decode($target);
    }
}

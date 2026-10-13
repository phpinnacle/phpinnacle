<?php

namespace PHPinnacle\Eidos\JsonSchema;

use Illuminate\JsonSchema\Deserializer as LaravelDeserializer;
use Illuminate\JsonSchema\Types\AnyOfType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\JsonSchema\Types\UnionType;
use InvalidArgumentException;
use WeakMap;

/** @internal */
final class Deserializer extends LaravelDeserializer
{
    /** @var WeakMap<Type, array<string, mixed>> */
    private readonly WeakMap $extraAttributes;

    /** @var WeakMap<AnyOfType, 'oneOf'|'allOf'> */
    private readonly WeakMap $compositions;

    /** @var WeakMap<AnyOfType, Type> */
    private readonly WeakMap $siblings;

    /** @param array<string, mixed> $root */
    protected function __construct(array $root)
    {
        parent::__construct($root);

        $this->extraAttributes = new WeakMap;
        $this->compositions = new WeakMap;
        $this->siblings = new WeakMap;
    }

    /** @param array<string, mixed> $schema */
    public static function deserialize(array $schema): Schema
    {
        $deserializer = new self($schema);

        return new Schema(
            $deserializer->build($schema),
            $deserializer->extraAttributes,
            $deserializer->compositions,
            $deserializer->siblings,
        );
    }

    /**
     * @param array<string, mixed> $schema
     * @param list<string> $refs
     */
    protected function buildAnyOfComposition(array $schema, array $refs = []): ?AnyOfType
    {
        foreach (['oneOf', 'allOf'] as $keyword) {
            if (!array_key_exists($keyword, $schema)) {
                continue;
            }

            $type = new AnyOfType(array_map(
                fn (array $branch) => $this->compositionBranch($branch, $refs),
                $schema[$keyword],
            ));
            $this->compositions->offsetSet($type, $keyword);
            unset($schema[$keyword]);

            if (array_intersect(array_keys($schema), [
                'type',
                'properties',
                'required',
                'items',
                'anyOf',
                'oneOf',
                'allOf',
                'additionalProperties',
                'minLength',
                'maxLength',
                'pattern',
                'format',
                'minimum',
                'maximum',
                'multipleOf',
                'minItems',
                'maxItems',
                'uniqueItems',
            ]) !== []) {
                $siblings = array_filter(
                    $schema,
                    fn (string $name) => (
                        !str_starts_with($name, 'x-')
                        && !in_array($name, ['title', 'description', 'default', 'enum'], true)
                    ),
                    ARRAY_FILTER_USE_KEY,
                );
                $this->siblings->offsetSet($type, $this->build($siblings, $refs));
            }

            return $type;
        }

        $type = parent::buildAnyOfComposition($schema, $refs);

        if ($type !== null || !is_array($schema['anyOf'] ?? null)) {
            return $type;
        }

        foreach ($schema['anyOf'] as $branch) {
            [$branch, $branchRefs] = $this->resolveRef($branch, $refs);

            if (array_key_exists('oneOf', $branch) || array_key_exists('allOf', $branch)) {
                return new AnyOfType([$this->build($branch, $branchRefs)])->nullable();
            }
        }

        return null;
    }

    /** @param array<string, mixed> $schema */
    protected function applyCommon(Type $type, array $schema): void
    {
        parent::applyCommon($type, $schema);

        $extraAttributes = [];

        foreach ($schema as $name => $value) {
            if (str_starts_with($name, 'x-')) {
                $extraAttributes[$name] = $value;
            }
        }

        if ($type instanceof ObjectType) {
            $required = array_values(array_diff($schema['required'] ?? [], array_keys($schema['properties'] ?? [])));

            if ($required !== []) {
                $extraAttributes['required'] = $required;
            }
        }

        if (array_key_exists('x-icon', $extraAttributes)) {
            $this->validateIcon($extraAttributes['x-icon']);
        }

        if (array_key_exists('x-color', $extraAttributes)) {
            $this->validateColor($extraAttributes['x-color']);
        }

        if ($extraAttributes !== []) {
            $this->extraAttributes->offsetSet($type, $extraAttributes);
        }
    }

    /**
     * @param array<string, mixed> $schema
     * @param list<string> $refs
     */
    private function compositionBranch(array $schema, array $refs): Type
    {
        [$schema, $refs] = $this->resolveRef($schema, $refs);

        if (!$this->isNullBranch($schema)) {
            return $this->build($schema, $refs);
        }

        $type = new UnionType(['null']);
        $this->applyCommon($type, $schema);

        return $type;
    }

    private function validateIcon(mixed $icon): void
    {
        if (!is_string($icon) || !preg_match('/^[a-zA-Z0-9_-]+$/D', $icon)) {
            throw new InvalidArgumentException('The JSON Schema [x-icon] must be a Blade icon name.');
        }
    }

    private function validateColor(mixed $color): void
    {
        $number = '[+-]?(?:\\d+(?:\\.\\d+)?|\\.\\d+)%?';
        $commaRgb = $number . '\\s*,\\s*' . $number . '\\s*,\\s*' . $number . '(?:\\s*,\\s*' . $number . ')?';
        $spaceRgb = $number . '\\s+' . $number . '\\s+' . $number . '(?:\\s*/\\s*' . $number . ')?';
        $pattern =
            '~^(?:[a-z][a-z0-9_-]*|\\#(?:[a-f0-9]{3,4}|[a-f0-9]{6}|[a-f0-9]{8})|rgba?\\(\\s*(?:'
            . $commaRgb
            . '|'
            . $spaceRgb
            . ')\\s*\\))$~iD';

        if (!is_string($color) || !preg_match($pattern, $color)) {
            throw new InvalidArgumentException(
                'The JSON Schema [x-color] must be a Filament color name, HEX or RGB/RGBA color.',
            );
        }
    }
}

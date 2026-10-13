<?php

namespace PHPinnacle\Eidos\JsonSchema;

use Illuminate\JsonSchema\Types\AnyOfType;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\Type;
use WeakMap;

final class Schema extends Type
{
    /** @var WeakMap<Type, array<string, mixed>> */
    private readonly WeakMap $extraAttributes;

    /** @var WeakMap<AnyOfType, 'oneOf'|'allOf'> */
    private readonly WeakMap $compositions;

    /** @var WeakMap<AnyOfType, Type> */
    private readonly WeakMap $siblings;

    /**
     * @param WeakMap<Type, array<string, mixed>>|null $extraAttributes
     * @param WeakMap<AnyOfType, 'oneOf'|'allOf'>|null $compositions
     * @param WeakMap<AnyOfType, Type>|null $siblings
     */
    public function __construct(
        private readonly Type $type,
        ?WeakMap $extraAttributes = null,
        ?WeakMap $compositions = null,
        ?WeakMap $siblings = null,
    ) {
        $this->extraAttributes = $extraAttributes ?? new WeakMap;
        $this->compositions = $compositions ?? new WeakMap;
        $this->siblings = $siblings ?? new WeakMap;
    }

    /** @param array<string, mixed> $schema */
    public static function fromArray(array $schema): self
    {
        return Deserializer::deserialize($schema);
    }

    /** @param list<Type> $schemas */
    public static function oneOf(array $schemas): self
    {
        return self::compose('oneOf', $schemas);
    }

    /** @param list<Type> $schemas */
    public static function allOf(array $schemas): self
    {
        return self::compose('allOf', $schemas);
    }

    public function required(bool $required = true): static
    {
        $this->type->required($required);

        return $this;
    }

    public function nullable(bool $nullable = true): static
    {
        $this->type->nullable($nullable);

        return $this;
    }

    public function title(string $value): static
    {
        $this->type->title($value);

        return $this;
    }

    public function description(string $value): static
    {
        $this->type->description($value);

        return $this;
    }

    /** @param class-string<\BackedEnum>|array<int, mixed> $values */
    public function enum(array|string $values): static
    {
        $this->type->enum($values);

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->annotate($this->type, $this->type->toArray());
    }

    /**
     * @param 'oneOf'|'allOf' $keyword
     * @param list<Type> $schemas
     */
    private static function compose(string $keyword, array $schemas): self
    {
        $type = new AnyOfType(array_map(fn (Type $schema) => $schema instanceof self
            ? $schema->type
            : $schema, $schemas));
        $composition = new self($type);
        $composition->compositions->offsetSet($type, $keyword);

        foreach ($schemas as $schema) {
            if (!$schema instanceof self) {
                continue;
            }

            foreach ($schema->extraAttributes as $node => $value) {
                $composition->extraAttributes->offsetSet($node, $value);
            }

            foreach ($schema->compositions as $node => $value) {
                $composition->compositions->offsetSet($node, $value);
            }

            foreach ($schema->siblings as $node => $value) {
                $composition->siblings->offsetSet($node, $value);
            }
        }

        return $composition;
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function annotate(Type $type, array $definition): array
    {
        $attributes = $this->extraAttributes[$type] ?? [];

        if (array_key_exists('required', $attributes)) {
            $attributes['required'] = [...($definition['required'] ?? []), ...$attributes['required']];
        }

        $definition = array_replace($definition, $attributes);

        if ($type instanceof ObjectType) {
            /** @var array<string, Type> $properties */
            $properties = (fn () => get_object_vars($type))->call($type)['properties'];

            foreach ($properties as $name => $property) {
                $definition['properties'][$name] = $this->annotate($property, $definition['properties'][$name]);
            }
        } elseif ($type instanceof ArrayType) {
            /** @var Type|null $items */
            $items = (fn () => get_object_vars($type))->call($type)['items'];

            if ($items !== null) {
                $definition['items'] = $this->annotate($items, $definition['items']);
            }
        } elseif ($type instanceof AnyOfType) {
            foreach ($type->schemas() as $index => $variant) {
                $definition['anyOf'][$index] = $this->annotate($variant, $definition['anyOf'][$index]);
            }

            $keyword = $this->compositions[$type] ?? null;

            if ($keyword !== null) {
                $nullable = array_key_exists(count($type->schemas()), $definition['anyOf']);

                if ($nullable) {
                    array_pop($definition['anyOf']);
                }

                $definition[$keyword] = $definition['anyOf'];
                unset($definition['anyOf']);

                $sibling = $this->siblings[$type] ?? null;

                if ($sibling !== null) {
                    $definition = array_replace($definition, $this->annotate($sibling, $sibling->toArray()));
                }

                if ($nullable) {
                    $definition = ['anyOf' => [$definition, ['type' => 'null']]];
                }
            }
        }

        return $definition;
    }
}

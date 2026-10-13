<?php

namespace PHPinnacle\Eidos\JsonSchema;

use Illuminate\JsonSchema\Serializer;
use Illuminate\JsonSchema\Types\AnyOfType;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\Type;
use PHPinnacle\Eidos\JsonSchema\Types\AllOfType;
use PHPinnacle\Eidos\JsonSchema\Types\CompositionType;
use PHPinnacle\Eidos\JsonSchema\Types\OneOfType;
use WeakMap;

final class Schema extends Type
{
    /** @var WeakMap<Type, array<string, mixed>> */
    private readonly WeakMap $extraAttributes;

    /**
     * @param WeakMap<Type, array<string, mixed>>|null $extraAttributes
     */
    public function __construct(
        private readonly Type $type,
        ?WeakMap $extraAttributes = null,
    ) {
        $this->extraAttributes = $extraAttributes ?? new WeakMap;
    }

    /** @param array<string, mixed> $schema */
    public static function fromArray(array $schema): self
    {
        return Deserializer::deserialize($schema);
    }

    /** @param list<Type> $schemas */
    public static function oneOf(array $schemas): self
    {
        return self::compose(OneOfType::class, $schemas);
    }

    /** @param list<Type> $schemas */
    public static function allOf(array $schemas): self
    {
        return self::compose(AllOfType::class, $schemas);
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
        return $this->annotate($this->type, Serializer::serialize($this->type));
    }

    /**
     * @param class-string<CompositionType> $typeClass
     * @param list<Type> $schemas
     */
    private static function compose(string $typeClass, array $schemas): self
    {
        $type = new $typeClass(array_map(fn (Type $schema) => $schema instanceof self
            ? $schema->type
            : $schema, $schemas));
        $composition = new self($type);

        foreach ($schemas as $schema) {
            if (!$schema instanceof self) {
                continue;
            }

            foreach ($schema->extraAttributes as $node => $value) {
                $composition->extraAttributes->offsetSet($node, $value);
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

            if ($type instanceof CompositionType) {
                $sibling = $type->sibling();

                return $type->compose(
                    $definition,
                    array_slice($definition['anyOf'], 0, count($type->schemas())),
                    $sibling === null ? null : $this->annotate($sibling, Serializer::serialize($sibling)),
                );
            }
        }

        return $definition;
    }
}

<?php

namespace PHPinnacle\Eidos\JsonSchema;

use Illuminate\JsonSchema\Types\AnyOfType;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\Type;
use WeakMap;

final class Schema extends Type
{
    /** @param WeakMap<Type, array<string, mixed>> $extraAttributes */
    public function __construct(
        private readonly Type $type,
        private readonly WeakMap $extraAttributes,
    ) {}

    /** @param array<string, mixed> $schema */
    public static function fromArray(array $schema): self
    {
        return Deserializer::deserialize($schema);
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
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function annotate(Type $type, array $definition): array
    {
        $definition = array_replace($definition, $this->extraAttributes[$type] ?? []);

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
        }

        return $definition;
    }
}

<?php

namespace PHPinnacle\Jason;

use BackedEnum;
use InvalidArgumentException;

final class SchemaBuilder
{
    /** @param array<string, mixed>|bool $definition */
    private function __construct(
        private array|bool $definition,
    ) {}

    public static function make(?Schema $schema = null): self
    {
        return new self($schema?->toArray() ?? []);
    }

    public function build(): Schema
    {
        return Schema::fromArray($this->definition);
    }

    public function with(string $keyword, mixed $value): self
    {
        if (is_bool($this->definition)) {
            throw new InvalidArgumentException('Boolean schemas cannot have keywords.');
        }

        $this->definition[$keyword] = Codec::copyValue($value);

        return $this;
    }

    /** @param string|list<string> $value */
    public function type(string|array $value): self
    {
        return $this->with('type', $value);
    }

    public function title(string $value): self
    {
        return $this->with('title', $value);
    }

    public function description(string $value): self
    {
        return $this->with('description', $value);
    }

    public function format(string $value): self
    {
        return $this->with('format', $value);
    }

    public function pattern(string $value): self
    {
        return $this->with('pattern', $value);
    }

    public function contentEncoding(string $value): self
    {
        return $this->with('contentEncoding', $value);
    }

    public function contentMediaType(string $value): self
    {
        return $this->with('contentMediaType', $value);
    }

    public function multipleOf(int|float $value): self
    {
        return $this->with('multipleOf', $value);
    }

    public function minimum(int|float $value): self
    {
        return $this->with('minimum', $value);
    }

    public function maximum(int|float $value): self
    {
        return $this->with('maximum', $value);
    }

    public function exclusiveMinimum(int|float $value): self
    {
        return $this->with('exclusiveMinimum', $value);
    }

    public function exclusiveMaximum(int|float $value): self
    {
        return $this->with('exclusiveMaximum', $value);
    }

    public function minLength(int $value): self
    {
        return $this->with('minLength', $value);
    }

    public function maxLength(int $value): self
    {
        return $this->with('maxLength', $value);
    }

    public function minItems(int $value): self
    {
        return $this->with('minItems', $value);
    }

    public function maxItems(int $value): self
    {
        return $this->with('maxItems', $value);
    }

    public function minContains(int $value): self
    {
        return $this->with('minContains', $value);
    }

    public function maxContains(int $value): self
    {
        return $this->with('maxContains', $value);
    }

    public function minProperties(int $value): self
    {
        return $this->with('minProperties', $value);
    }

    public function maxProperties(int $value): self
    {
        return $this->with('maxProperties', $value);
    }

    public function uniqueItems(bool $value): self
    {
        return $this->with('uniqueItems', $value);
    }

    public function readOnly(bool $value): self
    {
        return $this->with('readOnly', $value);
    }

    public function writeOnly(bool $value): self
    {
        return $this->with('writeOnly', $value);
    }

    public function deprecated(bool $value): self
    {
        return $this->with('deprecated', $value);
    }

    public function default(mixed $value): self
    {
        return $this->with('default', $value);
    }

    public function const(mixed $value): self
    {
        return $this->with('const', $value);
    }

    /** @param list<mixed>|class-string<BackedEnum> $value */
    public function enum(array|string $value): self
    {
        return $this->with(
            'enum',
            is_string($value) ? array_map(fn (BackedEnum $case) => $case->value, $value::cases()) : $value,
        );
    }

    /** @param list<string> $value */
    public function required(array $value): self
    {
        return $this->with('required', $value);
    }

    /** @param array<string, list<string>> $value */
    public function dependentRequired(array $value): self
    {
        return $this->with('dependentRequired', $value);
    }

    /** @param list<mixed> $value */
    public function examples(array $value): self
    {
        return $this->with('examples', $value);
    }

    /** @param array<string, bool> $value */
    public function vocabulary(array $value): self
    {
        return $this->with('$vocabulary', $value);
    }

    public function schema(string $value): self
    {
        return $this->with('$schema', $value);
    }

    public function id(string $value): self
    {
        return $this->with('$id', $value);
    }

    public function ref(string $value): self
    {
        return $this->with('$ref', $value);
    }

    public function anchor(string $value): self
    {
        return $this->with('$anchor', $value);
    }

    public function dynamicRef(string $value): self
    {
        return $this->with('$dynamicRef', $value);
    }

    public function dynamicAnchor(string $value): self
    {
        return $this->with('$dynamicAnchor', $value);
    }

    public function comment(string $value): self
    {
        return $this->with('$comment', $value);
    }

    /** @param array<string, Schema> $schemas */
    public function properties(array $schemas): self
    {
        return $this->with('properties', array_map(fn (Schema $schema) => $schema->toArray(), $schemas));
    }

    /** @param array<string, Schema> $schemas */
    public function patternProperties(array $schemas): self
    {
        return $this->with('patternProperties', array_map(fn (Schema $schema) => $schema->toArray(), $schemas));
    }

    /** @param array<string, Schema> $schemas */
    public function dependentSchemas(array $schemas): self
    {
        return $this->with('dependentSchemas', array_map(fn (Schema $schema) => $schema->toArray(), $schemas));
    }

    /** @param array<string, Schema> $schemas */
    public function defs(array $schemas): self
    {
        return $this->with('$defs', array_map(fn (Schema $schema) => $schema->toArray(), $schemas));
    }

    /** @param list<Schema> $schemas */
    public function prefixItems(array $schemas): self
    {
        return $this->with('prefixItems', array_map(fn (Schema $schema) => $schema->toArray(), $schemas));
    }

    /** @param list<Schema> $schemas */
    public function anyOf(array $schemas): self
    {
        return $this->with('anyOf', array_map(fn (Schema $schema) => $schema->toArray(), $schemas));
    }

    /** @param list<Schema> $schemas */
    public function oneOf(array $schemas): self
    {
        return $this->with('oneOf', array_map(fn (Schema $schema) => $schema->toArray(), $schemas));
    }

    /** @param list<Schema> $schemas */
    public function allOf(array $schemas): self
    {
        return $this->with('allOf', array_map(fn (Schema $schema) => $schema->toArray(), $schemas));
    }

    public function items(Schema $schema): self
    {
        return $this->with('items', $schema->toArray());
    }

    public function additionalProperties(Schema $schema): self
    {
        return $this->with('additionalProperties', $schema->toArray());
    }

    public function unevaluatedProperties(Schema $schema): self
    {
        return $this->with('unevaluatedProperties', $schema->toArray());
    }

    public function unevaluatedItems(Schema $schema): self
    {
        return $this->with('unevaluatedItems', $schema->toArray());
    }

    public function contains(Schema $schema): self
    {
        return $this->with('contains', $schema->toArray());
    }

    public function not(Schema $schema): self
    {
        return $this->with('not', $schema->toArray());
    }

    public function if(Schema $schema): self
    {
        return $this->with('if', $schema->toArray());
    }

    public function then(Schema $schema): self
    {
        return $this->with('then', $schema->toArray());
    }

    public function else(Schema $schema): self
    {
        return $this->with('else', $schema->toArray());
    }

    public function propertyNames(Schema $schema): self
    {
        return $this->with('propertyNames', $schema->toArray());
    }

    public function contentSchema(Schema $schema): self
    {
        return $this->with('contentSchema', $schema->toArray());
    }

    public function nullable(bool $nullable = true): self
    {
        $schema = $this->build();
        $definition = $schema->keywords();
        $type = $schema->type() ?? [];
        $types = (array) $type;
        if (
            $types !== []
            && !array_intersect([
                'enum',
                'const',
                'anyOf',
                'oneOf',
                'allOf',
                'not',
                'if',
                '$ref',
            ], array_keys($definition))
        ) {
            $types = array_values(array_diff($types, ['null']));
            if ($nullable) {
                $types[] = 'null';
            }

            if ($types === []) {
                $this->definition = false;

                return $this;
            }

            return $this->type(count($types) === 1 ? $types[0] : $types);
        }

        $schema = $schema->resolveReferences();
        if (!$nullable) {
            $this->definition = Schema::allOf([$schema, Schema::fromArray(['not' => ['type' => 'null']])])->toArray();

            return $this;
        }

        $this->definition = Schema::anyOf([$schema, Schema::null()])->toArray();

        return $this;
    }

    public function withoutAdditionalProperties(): self
    {
        return $this->additionalProperties(Schema::fromArray(false));
    }

    public function min(int|float $minimum): self
    {
        return $this->with(match ($this->definition['type'] ?? null) {
            'string' => 'minLength',
            'array' => 'minItems',
            'integer', 'number' => 'minimum',
            default => throw new InvalidArgumentException('min() requires a string, array, or numeric schema.'),
        }, $minimum);
    }

    public function max(int|float $maximum): self
    {
        return $this->with(match ($this->definition['type'] ?? null) {
            'string' => 'maxLength',
            'array' => 'maxItems',
            'integer', 'number' => 'maximum',
            default => throw new InvalidArgumentException('max() requires a string, array, or numeric schema.'),
        }, $maximum);
    }
}

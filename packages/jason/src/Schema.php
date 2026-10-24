<?php

namespace PHPinnacle\Jason;

use BackedEnum;
use Closure;
use InvalidArgumentException;
use JsonSerializable;
use stdClass;

/**
 * An immutable JSON Schema document or fragment. Unknown keywords remain intact.
 *
 * @phpstan-type Definition array<string, mixed>|bool
 */
final readonly class Schema implements JsonSerializable
{
    /** @param Definition $definition */
    private function __construct(
        private array|bool $definition,
    ) {}

    /** @param Definition $definition */
    public static function fromArray(array|bool $definition): self
    {
        return new self(Codec::copy($definition));
    }

    public static function fromJson(string $json): self
    {
        return self::fromArray(Codec::decode(json_decode($json, flags: JSON_THROW_ON_ERROR)));
    }

    /** @param array<string, self> $properties */
    public static function object(array $properties = []): self
    {
        return new self([
            'type' => 'object',
            'properties' => array_map(fn (self $schema) => $schema->toArray(), $properties),
        ]);
    }

    public static function array(): self
    {
        return new self(['type' => 'array']);
    }

    public static function string(): self
    {
        return new self(['type' => 'string']);
    }

    public static function integer(): self
    {
        return new self(['type' => 'integer']);
    }

    public static function number(): self
    {
        return new self(['type' => 'number']);
    }

    public static function boolean(): self
    {
        return new self(['type' => 'boolean']);
    }

    public static function null(): self
    {
        return new self(['type' => 'null']);
    }

    /** @param list<string> $types */
    public static function union(array $types): self
    {
        return new self(['type' => $types]);
    }

    /** @param list<self> $schemas */
    public static function anyOf(array $schemas): self
    {
        return new self(['anyOf' => array_map(fn (self $schema) => $schema->toArray(), $schemas)]);
    }

    /** @param list<self> $schemas */
    public static function oneOf(array $schemas): self
    {
        return new self(['oneOf' => array_map(fn (self $schema) => $schema->toArray(), $schemas)]);
    }

    /** @param list<self> $schemas */
    public static function allOf(array $schemas): self
    {
        return new self(['allOf' => array_map(fn (self $schema) => $schema->toArray(), $schemas)]);
    }

    public function with(string $keyword, mixed $value): self
    {
        if (is_bool($this->definition)) {
            throw new InvalidArgumentException('Boolean schemas cannot have keywords.');
        }

        return self::fromArray([...$this->definition, $keyword => $value]);
    }

    public function title(string $title): self
    {
        return $this->with('title', $title);
    }

    public function description(string $description): self
    {
        return $this->with('description', $description);
    }

    public function default(mixed $value): self
    {
        return $this->with('default', $value);
    }

    /** @param list<mixed>|class-string<BackedEnum> $values */
    public function enum(array|string $values): self
    {
        return $this->with(
            'enum',
            is_string($values) ? array_map(fn (BackedEnum $case) => $case->value, $values::cases()) : $values,
        );
    }

    /** @param list<string> $properties */
    public function required(array $properties): self
    {
        return $this->with('required', $properties);
    }

    public function nullable(bool $nullable = true): self
    {
        $definition = $this->keywords();
        /** @var string|list<string> $type */
        $type = $definition['type'] ?? [];
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

            return $types === []
                ? self::fromArray(false)
                : $this->with('type', count($types) === 1 ? $types[0] : $types);
        }

        $schema = $this->resolveReferences();
        if (!$nullable) {
            return self::allOf([$schema, self::fromArray(['not' => ['type' => 'null']])]);
        }

        return self::anyOf([$schema, self::null()]);
    }

    public function items(self $items): self
    {
        return $this->with('items', $items->toArray());
    }

    public function withoutAdditionalProperties(): self
    {
        return $this->with('additionalProperties', false);
    }

    public function format(string $format): self
    {
        return $this->with('format', $format);
    }

    public function pattern(string $pattern): self
    {
        return $this->with('pattern', $pattern);
    }

    public function min(int|float $minimum): self
    {
        return $this->with(match ($this->keyword('type')) {
            'string' => 'minLength',
            'array' => 'minItems',
            'integer', 'number' => 'minimum',
            default => throw new InvalidArgumentException('min() requires a string, array, or numeric schema.'),
        }, $minimum);
    }

    public function max(int|float $maximum): self
    {
        return $this->with(match ($this->keyword('type')) {
            'string' => 'maxLength',
            'array' => 'maxItems',
            'integer', 'number' => 'maximum',
            default => throw new InvalidArgumentException('max() requires a string, array, or numeric schema.'),
        }, $maximum);
    }

    /**
     * @return ($name is 'type' ? string|list<string>|null : ($name is 'required' ? list<string>|null : ($name is 'enum' ? list<mixed>|null : ($name is 'format'|'pattern'|'title'|'description'|'$ref' ? string|null : ($name is 'minimum'|'maximum'|'exclusiveMinimum'|'exclusiveMaximum'|'multipleOf' ? int|float|null : ($name is 'minLength'|'maxLength'|'minItems'|'maxItems' ? int|null : mixed))))))
     */
    public function keyword(string $name): mixed
    {
        return is_array($this->definition) ? Codec::copyValue($this->definition[$name] ?? null) : null;
    }

    public function hasKeyword(string $name): bool
    {
        return is_array($this->definition) && array_key_exists($name, $this->definition);
    }

    /** @return array<string, mixed> */
    public function keywords(): array
    {
        return is_array($this->definition) ? array_map(Codec::copyValue(...), $this->definition) : [];
    }

    /** @return array<string, self> */
    public function properties(): array
    {
        /** @var array<string, Definition> $properties */
        $properties = $this->keyword('properties') ?? [];

        return array_map(self::fromArray(...), $properties);
    }

    public function itemSchema(): ?self
    {
        /** @var Definition|null $items */
        $items = $this->keyword('items');

        return $items === null ? null : self::fromArray($items);
    }

    /**
     * @param 'anyOf'|'oneOf'|'allOf' $keyword
     * @return list<self>
     */
    public function branches(string $keyword): array
    {
        /** @var list<Definition> $branches */
        $branches = $this->keyword($keyword) ?? [];

        return array_map(self::fromArray(...), $branches);
    }

    /** @param Closure(self, ?string, string|int|null): void $visitor */
    public function walk(Closure $visitor): void
    {
        $this->walkAt($visitor);
    }

    /** @param Closure(self, ?string, string|int|null): void $visitor */
    private function walkAt(Closure $visitor, ?string $keyword = null, string|int|null $key = null): void
    {
        $visitor($this, $keyword, $key);

        if (is_bool($this->definition)) {
            return;
        }

        foreach ([...Codec::MAPS, ...Codec::LISTS] as $keyword) {
            /** @var array<array-key, Definition> $children */
            $children = $this->definition[$keyword] ?? [];
            foreach ($children as $key => $child) {
                new self($child)->walkAt(
                    $visitor,
                    $keyword,
                    in_array($keyword, Codec::MAPS, true) ? (string) $key : $key,
                );
            }
        }

        foreach (Codec::SCHEMAS as $keyword) {
            if (array_key_exists($keyword, $this->definition)) {
                /** @var Definition $child */
                $child = $this->definition[$keyword];
                new self($child)->walkAt($visitor, $keyword);
            }
        }
    }

    /** @param list<string|int|null> $segments Canonical JSONPath segments. */
    public function hasDeclaredPath(array $segments): bool
    {
        if ($segments === []) {
            return true;
        }

        foreach (['anyOf', 'oneOf', 'allOf'] as $keyword) {
            foreach ($this->branches($keyword) as $branch) {
                if ($branch->hasDeclaredPath($segments)) {
                    return true;
                }
            }
        }

        $segment = $segments[0];
        $child = is_string($segment) ? $this->properties()[$segment] ?? null : $this->itemSchema();

        return $child !== null && $child->hasDeclaredPath(array_slice($segments, 1));
    }

    public function resolveReferences(): self
    {
        return new References($this)->resolve();
    }

    /** @return Definition */
    public function toArray(): array|bool
    {
        return Codec::copy($this->definition);
    }

    public function toJson(): string
    {
        return json_encode(
            $this,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION,
        );
    }

    public function jsonSerialize(): stdClass|bool
    {
        return Codec::encode($this->toArray());
    }
}

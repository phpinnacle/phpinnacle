<?php

namespace PHPinnacle\Jason;

use Closure;
use JsonSerializable;
use stdClass;

/**
 * An immutable JSON Schema document or fragment. Unknown keywords remain intact.
 *
 * @phpstan-type Definition array<string, mixed>|bool
 * @phpstan-type Keywords array{
 *     type?: string|list<string>,
 *     title?: string,
 *     description?: string,
 *     format?: string,
 *     pattern?: string,
 *     contentEncoding?: string,
 *     contentMediaType?: string,
 *     multipleOf?: int|float,
 *     minimum?: int|float,
 *     maximum?: int|float,
 *     exclusiveMinimum?: int|float,
 *     exclusiveMaximum?: int|float,
 *     minLength?: int,
 *     maxLength?: int,
 *     minItems?: int,
 *     maxItems?: int,
 *     minContains?: int,
 *     maxContains?: int,
 *     minProperties?: int,
 *     maxProperties?: int,
 *     uniqueItems?: bool,
 *     readOnly?: bool,
 *     writeOnly?: bool,
 *     deprecated?: bool,
 *     default?: mixed,
 *     const?: mixed,
 *     enum?: list<mixed>,
 *     required?: list<string>,
 *     dependentRequired?: array<string, list<string>>,
 *     examples?: list<mixed>,
 *     '$vocabulary'?: array<string, bool>,
 *     '$schema'?: string,
 *     '$id'?: string,
 *     '$ref'?: string,
 *     '$anchor'?: string,
 *     '$dynamicRef'?: string,
 *     '$dynamicAnchor'?: string,
 *     '$comment'?: string,
 *     properties?: array<string, Definition>,
 *     patternProperties?: array<string, Definition>,
 *     dependentSchemas?: array<string, Definition>,
 *     '$defs'?: array<string, Definition>,
 *     items?: Definition,
 *     additionalProperties?: Definition,
 *     unevaluatedProperties?: Definition,
 *     unevaluatedItems?: Definition,
 *     contains?: Definition,
 *     not?: Definition,
 *     if?: Definition,
 *     then?: Definition,
 *     else?: Definition,
 *     propertyNames?: Definition,
 *     contentSchema?: Definition,
 *     prefixItems?: list<Definition>,
 *     allOf?: list<Definition>,
 *     anyOf?: list<Definition>,
 *     oneOf?: list<Definition>,
 * }
 */
final readonly class Schema implements JsonSerializable
{
    /** @param Keywords|bool $definition */
    private function __construct(
        private array|bool $definition,
    ) {}

    /** @param Definition $definition */
    public static function fromArray(array|bool $definition): self
    {
        /** @var Keywords|bool $copy */
        $copy = Codec::copy($definition);

        return new self($copy);
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

    public function builder(): SchemaBuilder
    {
        return SchemaBuilder::make($this);
    }

    public function allowsAny(): bool
    {
        return $this->definition === true;
    }

    public function deniesAny(): bool
    {
        return $this->definition === false;
    }

    /** @return string|list<string>|null */
    public function type(): string|array|null
    {
        return $this->keywordVal('type');
    }

    public function title(): ?string
    {
        return $this->keywordVal('title');
    }

    public function description(): ?string
    {
        return $this->keywordVal('description');
    }

    public function format(): ?string
    {
        return $this->keywordVal('format');
    }

    public function pattern(): ?string
    {
        return $this->keywordVal('pattern');
    }

    public function contentEncoding(): ?string
    {
        return $this->keywordVal('contentEncoding');
    }

    public function contentMediaType(): ?string
    {
        return $this->keywordVal('contentMediaType');
    }

    public function multipleOf(): int|float|null
    {
        return $this->keywordVal('multipleOf');
    }

    public function minimum(): int|float|null
    {
        return $this->keywordVal('minimum');
    }

    public function maximum(): int|float|null
    {
        return $this->keywordVal('maximum');
    }

    public function exclusiveMinimum(): int|float|null
    {
        return $this->keywordVal('exclusiveMinimum');
    }

    public function exclusiveMaximum(): int|float|null
    {
        return $this->keywordVal('exclusiveMaximum');
    }

    public function minLength(): ?int
    {
        return $this->keywordVal('minLength');
    }

    public function maxLength(): ?int
    {
        return $this->keywordVal('maxLength');
    }

    public function minItems(): ?int
    {
        return $this->keywordVal('minItems');
    }

    public function maxItems(): ?int
    {
        return $this->keywordVal('maxItems');
    }

    public function minContains(): ?int
    {
        return $this->keywordVal('minContains');
    }

    public function maxContains(): ?int
    {
        return $this->keywordVal('maxContains');
    }

    public function minProperties(): ?int
    {
        return $this->keywordVal('minProperties');
    }

    public function maxProperties(): ?int
    {
        return $this->keywordVal('maxProperties');
    }

    public function uniqueItems(): ?bool
    {
        return $this->keywordVal('uniqueItems');
    }

    public function readOnly(): ?bool
    {
        return $this->keywordVal('readOnly');
    }

    public function writeOnly(): ?bool
    {
        return $this->keywordVal('writeOnly');
    }

    public function deprecated(): ?bool
    {
        return $this->keywordVal('deprecated');
    }

    public function default(): mixed
    {
        return Codec::copyValue($this->keywordVal('default'));
    }

    public function const(): mixed
    {
        return Codec::copyValue($this->keywordVal('const'));
    }

    /** @return list<mixed>|null */
    public function enum(): ?array
    {
        $values = $this->keywordVal('enum');

        return $values === null ? null : array_map(Codec::copyValue(...), $values);
    }

    /** @return list<string> */
    public function required(): array
    {
        return $this->keywordVal('required') ?? [];
    }

    /** @return array<string, list<string>> */
    public function dependentRequired(): array
    {
        return $this->keywordVal('dependentRequired') ?? [];
    }

    /** @return list<mixed> */
    public function examples(): array
    {
        return array_map(Codec::copyValue(...), $this->keywordVal('examples') ?? []);
    }

    /** @return array<string, bool> */
    public function vocabulary(): array
    {
        return $this->keywordVal('$vocabulary') ?? [];
    }

    public function schema(): ?string
    {
        return $this->keywordVal('$schema');
    }

    public function id(): ?string
    {
        return $this->keywordVal('$id');
    }

    public function ref(): ?string
    {
        return $this->keywordVal('$ref');
    }

    public function anchor(): ?string
    {
        return $this->keywordVal('$anchor');
    }

    public function dynamicRef(): ?string
    {
        return $this->keywordVal('$dynamicRef');
    }

    public function dynamicAnchor(): ?string
    {
        return $this->keywordVal('$dynamicAnchor');
    }

    public function comment(): ?string
    {
        return $this->keywordVal('$comment');
    }

    /** @return array<string, self> */
    public function properties(): array
    {
        return array_map(self::fromArray(...), $this->keywordVal('properties') ?? []);
    }

    /** @return array<string, self> */
    public function patternProperties(): array
    {
        return array_map(self::fromArray(...), $this->keywordVal('patternProperties') ?? []);
    }

    /** @return array<string, self> */
    public function dependentSchemas(): array
    {
        return array_map(self::fromArray(...), $this->keywordVal('dependentSchemas') ?? []);
    }

    /** @return array<string, self> */
    public function defs(): array
    {
        return array_map(self::fromArray(...), $this->keywordVal('$defs') ?? []);
    }

    public function items(): ?self
    {
        $definition = $this->keywordVal('items');

        return $definition === null ? null : self::fromArray($definition);
    }

    public function additionalProperties(): ?self
    {
        $definition = $this->keywordVal('additionalProperties');

        return $definition === null ? null : self::fromArray($definition);
    }

    public function unevaluatedProperties(): ?self
    {
        $definition = $this->keywordVal('unevaluatedProperties');

        return $definition === null ? null : self::fromArray($definition);
    }

    public function unevaluatedItems(): ?self
    {
        $definition = $this->keywordVal('unevaluatedItems');

        return $definition === null ? null : self::fromArray($definition);
    }

    public function contains(): ?self
    {
        $definition = $this->keywordVal('contains');

        return $definition === null ? null : self::fromArray($definition);
    }

    public function not(): ?self
    {
        $definition = $this->keywordVal('not');

        return $definition === null ? null : self::fromArray($definition);
    }

    public function if(): ?self
    {
        $definition = $this->keywordVal('if');

        return $definition === null ? null : self::fromArray($definition);
    }

    public function then(): ?self
    {
        $definition = $this->keywordVal('then');

        return $definition === null ? null : self::fromArray($definition);
    }

    public function else(): ?self
    {
        $definition = $this->keywordVal('else');

        return $definition === null ? null : self::fromArray($definition);
    }

    public function propertyNames(): ?self
    {
        $definition = $this->keywordVal('propertyNames');

        return $definition === null ? null : self::fromArray($definition);
    }

    public function contentSchema(): ?self
    {
        $definition = $this->keywordVal('contentSchema');

        return $definition === null ? null : self::fromArray($definition);
    }

    /** @return list<self> */
    public function prefixItems(): array
    {
        return array_map(self::fromArray(...), $this->keywordVal('prefixItems') ?? []);
    }

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

    /**
     * @param 'anyOf'|'oneOf'|'allOf' $keyword
     * @return list<self>
     */
    public function branches(string $keyword): array
    {
        $branches = $this->keywordVal($keyword) ?? [];

        return array_map(self::fromArray(...), $branches);
    }

    /**
     * @template TKeyword of key-of<Keywords>
     * @param TKeyword $keyword
     * @return Keywords[TKeyword]|null
     */
    private function keywordVal(string $keyword): mixed
    {
        return is_array($this->definition) ? $this->definition[$keyword] ?? null : null;
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
            /** @var array<array-key, Keywords|bool> $children */
            $children = $this->definition[$keyword] ?? [];
            foreach ($children as $key => $child) {
                self::fromArray($child)
                    ->walkAt(
                        $visitor,
                        $keyword,
                        in_array($keyword, Codec::MAPS, true) ? (string) $key : $key,
                    );
            }
        }

        foreach (Codec::SCHEMAS as $keyword) {
            if (array_key_exists($keyword, $this->definition)) {
                /** @var Keywords|bool $child */
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
        $child = is_string($segment) ? $this->properties()[$segment] ?? null : $this->items();

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

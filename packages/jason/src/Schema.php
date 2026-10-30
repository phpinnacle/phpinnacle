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
 *     oneOf?: list<Definition>, ...<string, mixed>
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

    /** @return Keywords */
    public function keywords(): array
    {
        /** @var Keywords */
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

    /** @return array<string, self> */
    public function effectiveProperties(): array
    {
        $properties = $this->properties();
        foreach ($this->branches('allOf') as $branch) {
            foreach ($branch->effectiveProperties() as $name => $property) {
                $properties[$name] = array_key_exists($name, $properties)
                    ? self::compose('allOf', [$properties[$name], $property])
                    : $property;
            }
        }

        return $properties;
    }

    /** @return list<string> */
    public function effectiveRequired(): array
    {
        $required = $this->required();
        foreach ($this->branches('allOf') as $branch) {
            $required = array_merge($required, $branch->effectiveRequired());
        }

        return array_values(array_unique($required));
    }

    public function property(string $name): ?self
    {
        $properties = $this->effectiveProperties();
        if (array_key_exists($name, $properties)) {
            return $properties[$name];
        }

        $additional = $this->additionalProperties();
        if ($additional?->deniesAny()) {
            return null;
        }

        return $additional === null || $additional->allowsAny() ? self::fromArray([]) : $additional;
    }

    public function arrayItems(): ?self
    {
        if (array_values(array_diff($this->types(), ['null'])) !== ['array']) {
            return null;
        }

        return $this->effectiveItems() ?? self::fromArray([]);
    }

    public function effectiveItems(): ?self
    {
        $items = $this->items();
        $constraints = $items === null ? [] : [$items];
        foreach ($this->branches('allOf') as $branch) {
            $items = $branch->effectiveItems();
            if ($items !== null) {
                $constraints[] = $items;
            }
        }

        foreach ([$this->branches('anyOf'), $this->branches('oneOf')] as $branches) {
            $items = [];
            foreach ($branches as $branch) {
                if ($branch->types() === [] || in_array('array', $branch->types(), true)) {
                    $items[] = $branch->effectiveItems() ?? self::fromArray([]);
                }
            }

            if ($items !== []) {
                $constraints[] = self::compose('anyOf', $items);
            }
        }

        return $constraints === [] ? null : self::compose('allOf', $constraints);
    }

    /**
     * @param 'allOf'|'anyOf' $keyword
     * @param non-empty-list<self> $schemas
     */
    private static function compose(string $keyword, array $schemas): self
    {
        if (count($schemas) === 1) {
            return $schemas[0];
        }

        return self::fromArray([$keyword => array_map(fn (self $schema) => $schema->toArray(), $schemas)]);
    }

    /** @return list<string> */
    public function types(): array
    {
        $types = (array) ($this->type() ?? []);
        foreach ([...$this->branches('anyOf'), ...$this->branches('oneOf')] as $branch) {
            $types = array_merge($types, $branch->types());
        }

        foreach ($this->branches('allOf') as $branch) {
            $branchTypes = $branch->types();
            if ($branchTypes !== []) {
                if (in_array('number', $types, true) && in_array('integer', $branchTypes, true)) {
                    $types[] = 'integer';
                }

                if (in_array('integer', $types, true) && in_array('number', $branchTypes, true)) {
                    $branchTypes[] = 'integer';
                }

                $types = $types === [] ? $branchTypes : array_intersect($types, $branchTypes);
            }
        }

        return array_values(array_unique($types));
    }

    public function singleType(): ?string
    {
        $types = array_values(array_diff($this->types(), ['null']));

        if (count($types) === 1) {
            return $types[0];
        }

        return $this->types() === ['null'] ? 'null' : null;
    }

    public function effectiveFormat(): ?string
    {
        return $this->annotation('format');
    }

    /** @return ($name is 'title'|'description'|'format' ? string|null : mixed) */
    public function annotation(string $name): mixed
    {
        if ($this->hasKeyword($name)) {
            return $this->keyword($name);
        }

        foreach ([...$this->branches('allOf'), ...$this->branches('anyOf'), ...$this->branches('oneOf')] as $branch) {
            $value = $branch->annotation($name);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    public function equals(self $other): bool
    {
        return self::sameJsonValue($this->definition, $other->definition);
    }

    /** @return list<mixed>|null */
    public function enumValues(): ?array
    {
        $values = $this->enum() ?? null;
        foreach ($this->branches('allOf') as $branch) {
            $allowed = $branch->enumValues();
            if ($allowed !== null) {
                $values = $values === null
                    ? $allowed
                    : array_values(array_filter(
                        $values,
                        fn (mixed $value) => array_any($allowed, fn (mixed $candidate) => self::sameJsonValue(
                            $value,
                            $candidate,
                        )),
                    ));
            }
        }

        return $values;
    }

    private static function sameJsonValue(mixed $left, mixed $right): bool
    {
        return match (true) {
            is_int($left) && is_float($right) => $left === (int) $right && (float) $left === $right,
            is_float($left) && is_int($right) => self::sameJsonValue($right, $left),
            (is_array($left) || $left instanceof stdClass) && (is_array($right) || $right instanceof stdClass)
                => self::sameJsonStructure($left, $right),
            default => $left === $right,
        };
    }

    /**
     * @param array<array-key, mixed>|stdClass $left
     * @param array<array-key, mixed>|stdClass $right
     */
    private static function sameJsonStructure(array|stdClass $left, array|stdClass $right): bool
    {
        $leftIsList = is_array($left) && array_is_list($left);
        $rightIsList = is_array($right) && array_is_list($right);
        $left = (array) $left;
        $right = (array) $right;
        if ($leftIsList !== $rightIsList || count($left) !== count($right)) {
            return false;
        }

        return array_all(
            $left,
            fn (mixed $value, int|string $key) => (
                array_key_exists($key, $right) && self::sameJsonValue($value, $right[$key])
            ),
        );
    }

    public function effectiveMaxLength(): ?int
    {
        $alternatives = $this->alternatives();
        if ($alternatives === []) {
            return $this->lengthBound('maxLength');
        }

        $bounds = [];
        foreach ($alternatives as $branch) {
            if ($branch->types() !== [] && !in_array('string', $branch->types(), true)) {
                continue;
            }

            $bound = $branch->effectiveMaxLength();
            if ($bound === null) {
                return null;
            }

            $bounds[] = $bound;
        }

        return $bounds === [] ? null : max($bounds);
    }

    /** @param 'minLength'|'maxLength' $keyword */
    private function lengthBound(string $keyword): ?int
    {
        $bound = $keyword === 'minLength' ? $this->minLength() : $this->maxLength();
        foreach ($this->branches('allOf') as $branch) {
            $value = $branch->lengthBound($keyword);
            if ($value === null) {
                continue;
            }

            if ($bound === null) {
                $bound = $value;

                continue;
            }

            $bound = $keyword === 'minLength' ? max($bound, $value) : min($bound, $value);
        }

        return $bound;
    }

    /** @return list<self> */
    public function alternatives(): array
    {
        $branches = $this->branches('anyOf');
        $keyword = $branches !== [] ? 'anyOf' : 'oneOf';
        $branches = $branches !== [] ? $branches : $this->branches('oneOf');
        $siblings = $this->keywords();
        unset($siblings[$keyword]);

        if ($branches === []) {
            foreach ($this->branches('allOf') as $index => $branch) {
                $alternatives = $branch->alternatives();
                if ($alternatives !== []) {
                    $allOf = $this->branches('allOf');
                    unset($allOf[$index]);
                    $siblings['allOf'] = array_values(array_map(fn (Schema $schema) => $schema->toArray(), $allOf));

                    $branches = $alternatives;

                    break;
                }
            }
        }

        if ($branches === []) {
            return [];
        }

        $siblings = self::fromArray($siblings);

        return array_map(fn (self $branch) => self::compose('allOf', [$siblings, $branch]), $branches);
    }

    public function effectiveMinLength(): ?int
    {
        return $this->lengthBound('minLength');
    }

    public function allowsEnumValue(mixed $value): bool
    {
        $values = $this->enumValues();

        return $values === null || array_any($values, fn (mixed $allowed) => self::sameJsonValue($value, $allowed));
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

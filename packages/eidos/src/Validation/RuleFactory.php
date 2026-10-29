<?php

namespace PHPinnacle\Eidos\Validation;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use PHPinnacle\Jason\Schema;

class RuleFactory
{
    /**
     * @param array<string, mixed> $rules
     * @return array<string, mixed>
     */
    public function make(Schema $schema, array $rules = [], string $prefix = ''): array
    {
        return $this->merge($rules, $this->schemaRules($schema->resolveReferences(), $prefix));
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function schemaRules(Schema $schema, string $prefix): array
    {
        if ($schema->allowsAny()) {
            return [];
        }

        if ($schema->deniesAny()) {
            if ($prefix === '') {
                throw new InvalidArgumentException('A rejecting schema requires an attribute prefix.');
            }

            return [$prefix => [new FalseSchema]];
        }

        $rules = $prefix === '' ? [] : [$prefix => $this->fieldRules($schema)];

        foreach ($schema->properties() as $name => $property) {
            $key = $prefix === '' ? (string) $name : $prefix . '.' . $name;
            $rules = $this->merge($rules, $this->schemaRules($property, $key));
        }

        foreach ($schema->required() as $name) {
            $key = $prefix === '' ? $name : $prefix . '.' . $name;
            $rules[$key] = [$prefix === '' ? 'required' : 'required_with:' . $prefix, ...($rules[$key] ?? [])];
        }

        $items = $schema->items();
        if ($items !== null) {
            $rules = $this->merge($rules, $this->schemaRules($items, $prefix === '' ? '*' : $prefix . '.*'));
        }

        foreach (['anyOf', 'oneOf'] as $keyword) {
            if (!$schema->hasKeyword($keyword)) {
                continue;
            }

            if ($prefix === '') {
                throw new InvalidArgumentException('Alternative schema validation requires an attribute prefix.');
            }

            $rules[$prefix][] = $this->alternatives($schema->branches($keyword), $keyword === 'oneOf');
        }

        foreach ($schema->branches('allOf') as $branch) {
            $rules = $this->merge($rules, $this->schemaRules($branch, $prefix));
        }

        return $rules;
    }

    /**
     * @return list<mixed>
     */
    private function fieldRules(Schema $schema): array
    {
        $allTypes = (array) ($schema->type() ?? []);
        $types = array_values(array_diff($allTypes, ['null']));
        $nullable = in_array('null', $allTypes, true);
        $rules = $nullable ? ['nullable'] : [];

        if (count($types) > 1) {
            $rules[] = new SchemaAlternatives(
                fn (string $attribute) => array_map(fn (string $type) => [$attribute => [
                    ...$this->typeRules($schema, $type),
                    ...$this->constraintRules($schema, $type),
                ]], $allTypes),
                false,
            );
        } else {
            $type = $types[0] ?? ($nullable ? 'null' : null);
            $rules = [
                ...$rules,
                ...$this->typeRules($schema, $type),
                ...$this->constraintRules($schema, $type),
            ];
        }

        return array_values([
            ...$rules,
            ...Arr::wrap($schema->keyword('x-validation')),
        ]);
    }

    /**
     * @return list<mixed>
     */
    private function typeRules(Schema $schema, ?string $type): array
    {
        return match ($type) {
            'string' => match ($schema->format()) {
                'uuid' => ['uuid'],
                'email' => ['email'],
                'date', 'date-time' => ['date'],
                default => ['string'],
            },
            'integer' => ['integer'],
            'number' => ['numeric'],
            'boolean' => ['boolean'],
            'null' => [new NullSchema],
            'array', 'object' => ['array'],
            default => [],
        };
    }

    /**
     * @return list<mixed>
     */
    private function constraintRules(Schema $schema, ?string $type): array
    {
        $rules = [];
        $boundType = $type === 'integer' ? 'number' : $type;

        foreach ([
            [$schema->minLength(), 'min', 'string'],
            [$schema->maxLength(), 'max', 'string'],
            [$schema->minimum(), 'min', 'number'],
            [$schema->maximum(), 'max', 'number'],
            [$schema->minItems(), 'min', 'array'],
            [$schema->maxItems(), 'max', 'array'],
        ] as [$value, $rule, $constraintType]) {
            if ($value !== null && ($boundType === null || $boundType === $constraintType)) {
                $rules[] = $rule . ':' . $value;
            }
        }

        $enum = $schema->enum();
        if ($enum !== null) {
            $rules[] = Rule::in($enum);
        }

        $required = $schema->required();
        if ($required !== []) {
            $rules[] = 'required_array_keys:' . implode(',', $required);
        }

        return $rules;
    }

    /** @param list<Schema> $schemas */
    private function alternatives(array $schemas, bool $exactlyOne = false): SchemaAlternatives
    {
        return new SchemaAlternatives(
            fn (string $attribute) => array_map(fn (Schema $schema) => $this->schemaRules(
                $schema,
                $attribute,
            ), $schemas),
            $exactlyOne,
        );
    }

    /**
     * @template TValue
     * @param array<string, TValue> $left
     * @param array<string, list<mixed>> $right
     * @return array<string, TValue|list<mixed>>
     */
    private function merge(array $left, array $right): array
    {
        foreach ($right as $key => $rules) {
            $left[$key] = array_values(array_unique([
                ...Arr::wrap($left[$key] ?? []),
                ...$rules,
            ], SORT_REGULAR));
        }

        return $left;
    }
}

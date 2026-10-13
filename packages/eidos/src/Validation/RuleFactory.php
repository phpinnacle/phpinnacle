<?php

namespace PHPinnacle\Eidos\Validation;

use Closure;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class RuleFactory
{
    /**
     * @param array<string, mixed> $rules
     * @return array<string, mixed>
     */
    public function make(Type $schema, array $rules = [], string $prefix = ''): array
    {
        return $this->merge($rules, $this->schemaRules($schema->toArray(), $prefix));
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<string, list<mixed>>
     */
    private function schemaRules(array $schema, string $prefix): array
    {
        $rules = $prefix === '' ? [] : [$prefix => $this->fieldRules($schema)];

        foreach ($schema['properties'] ?? [] as $name => $property) {
            $key = $prefix === '' ? (string) $name : $prefix . '.' . $name;
            $rules = $this->merge($rules, $this->schemaRules($property, $key));
        }

        foreach ($schema['required'] ?? [] as $name) {
            $key = $prefix === '' ? $name : $prefix . '.' . $name;
            $rules[$key] = [$prefix === '' ? 'required' : 'required_with:' . $prefix, ...($rules[$key] ?? [])];
        }

        if (array_key_exists('items', $schema)) {
            $rules = $this->merge($rules, $this->schemaRules($schema['items'], $prefix === '' ? '*' : $prefix . '.*'));
        }

        foreach (['anyOf', 'oneOf'] as $keyword) {
            if (!array_key_exists($keyword, $schema)) {
                continue;
            }

            if ($prefix === '') {
                throw new InvalidArgumentException('Alternative schema validation requires an attribute prefix.');
            }

            $rules[$prefix][] = $this->alternatives($schema[$keyword], $keyword === 'oneOf');
        }

        foreach ($schema['allOf'] ?? [] as $branch) {
            $rules = $this->merge($rules, $this->schemaRules($branch, $prefix));
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $schema
     * @return list<mixed>
     */
    private function fieldRules(array $schema): array
    {
        $types = (array) ($schema['type'] ?? []);
        $rules = in_array('null', $types, true) ? ['nullable'] : [];
        $types = array_values(array_diff($types, ['null']));

        if (count($types) > 1) {
            $rules[] = $this->alternatives(array_map(
                fn (string $type) => array_replace($schema, ['type' => $type]),
                $types,
            ));
        } else {
            $rules = [
                ...$rules,
                ...match ($types[0] ?? null) {
                    'string' => match ($schema['format'] ?? null) {
                        'uuid' => ['uuid'],
                        'email' => ['email'],
                        'date', 'date-time' => ['date'],
                        default => ['string'],
                    },
                    'integer' => ['integer'],
                    'number' => ['numeric'],
                    'boolean' => ['boolean'],
                    'array', 'object' => ['array'],
                    default => [],
                },
            ];
        }

        if ($types === [] && in_array('null', (array) ($schema['type'] ?? []), true)) {
            $rules[] = function (string $attribute, mixed $value, Closure $fail) {
                if ($value !== null) {
                    $fail('validation.in')->translate();
                }
            };
        }

        foreach ([
            'minLength' => 'min',
            'maxLength' => 'max',
            'minimum' => 'min',
            'maximum' => 'max',
            'minItems' => 'min',
            'maxItems' => 'max',
        ] as $constraint => $rule) {
            if (array_key_exists($constraint, $schema)) {
                $rules[] = $rule . ':' . $schema[$constraint];
            }
        }

        if (array_key_exists('enum', $schema)) {
            $rules[] = Rule::in($schema['enum']);
        }

        if (($schema['required'] ?? []) !== []) {
            $rules[] = 'required_array_keys:' . implode(',', $schema['required']);
        }

        return [...$rules, ...Arr::wrap($schema['x-validation'] ?? [])];
    }

    /** @param list<array<string, mixed>> $schemas */
    private function alternatives(array $schemas, bool $exactlyOne = false): SchemaAlternatives
    {
        return new SchemaAlternatives(
            fn (string $attribute) => array_map(fn (array $schema) => $this->schemaRules(
                $schema,
                $attribute,
            ), $schemas),
            $exactlyOne,
        );
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, list<mixed>> $right
     * @return array<string, mixed>
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

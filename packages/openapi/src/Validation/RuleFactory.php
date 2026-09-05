<?php

namespace PHPinnacle\OpenApi\Validation;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use OpenApi\Annotations as OA;
use OpenApi\Undefined;

class RuleFactory
{
    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public function make(OA\Schema $schema, array $rules, string $prefix = ''): array
    {
        $rules = $this->walkProperties($schema, $rules, $prefix);

        if (!Undefined::isDefault($schema->oneOf)) {
            if ($prefix === '' || $schema->type !== 'object') {
                throw new InvalidArgumentException('OneOf validation requires a prefixed object schema.');
            }

            $rules = $this->merge($rules, [
                $prefix => ['array', $this->oneOfRule($schema->oneOf)],
            ]);
        }

        if (!Undefined::isDefault($schema->allOf)) {
            foreach ($schema->allOf as $item) {
                $rules = $this->merge($rules, $this->make($item, $rules, $prefix));
            }
        }

        return $rules;
    }

    /**
     * @param  array<OA\Schema>  $schemas
     */
    private function oneOfRule(array $schemas): Closure
    {
        $requiredSets = array_map(function (OA\Schema $schema) {
            if (Undefined::isDefault($schema->required) || $schema->required === []) {
                throw new InvalidArgumentException('Each oneOf schema must define required properties.');
            }

            return $schema->required;
        }, $schemas);

        return function (string $attribute, mixed $value, Closure $fail) use ($requiredSets) {
            if (!is_array($value)) {
                return;
            }

            $matches = count(array_filter(
                $requiredSets,
                fn (array $required) => array_all(
                    $required,
                    fn (string $property) => filled(Arr::get($value, $property)),
                ),
            ));

            if ($matches !== 1) {
                $fail('phpinnacle-openapi::messages.one_of')->translate();
            }
        };
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function walkProperties(OA\Schema $schema, array $rules, string $prefix): array
    {
        if (Undefined::isDefault($schema->properties)) {
            return $rules;
        }

        foreach ($schema->properties as $property) {
            $key = $prefix !== '' ? sprintf('%s.%s', $prefix, $property->property) : $property->property;

            $rules[$key] = $this->fieldRules($property);

            if (!Undefined::isDefault($property->properties)) {
                $rules = $this->make($property, $rules, $key);
            }
        }

        return $rules;
    }

    /** @return list<mixed> */
    private function fieldRules(OA\Property $property): array
    {
        $rules = [];

        if ($property->nullable === true) {
            $rules[] = 'nullable';
        }

        $rules = [
            ...$rules,
            ...$this->typeRules($property),
        ];

        if (!Undefined::isDefault($property->minLength)) {
            $rules[] = 'min:' . $property->minLength;
        }

        if (!Undefined::isDefault($property->maxLength)) {
            $rules[] = 'max:' . $property->maxLength;
        }

        if (!Undefined::isDefault($property->minimum)) {
            $rules[] = 'min:' . $property->minimum;
        }

        if (!Undefined::isDefault($property->maximum)) {
            $rules[] = 'max:' . $property->maximum;
        }

        if (is_array($property->enum)) {
            $rules[] = Rule::in($property->enum);
        }

        if (!Undefined::isDefault($property->properties)) {
            $rules[] = 'array';
        }

        if (($property->x['validation'] ?? null) !== null) {
            $rules = array_values(array_merge($rules, Arr::wrap($property->x['validation'])));
        }

        return $rules;
    }

    /** @return list<string> */
    private function typeRules(OA\Property $property): array
    {
        return match ($property->type) {
            'string' => match ($property->format) {
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
        };
    }

    /**
     * @param  array<string, mixed>  $left
     * @param  array<string, mixed>  $right
     * @return array<string, mixed>
     */
    private function merge(array $left, array $right): array
    {
        foreach ($right as $key => $rules) {
            $left[$key] = array_values(array_unique([
                ...Arr::wrap($left[$key] ?? []),
                ...Arr::wrap($rules),
            ], SORT_REGULAR));
        }

        return $left;
    }
}

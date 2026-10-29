<?php

namespace PHPinnacle\Eidos\Validation;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

/** @internal */
final class SchemaAlternatives implements DataAwareRule, ValidationRule
{
    public bool $implicit = true;

    /** @var array<string, mixed> */
    private array $data = [];

    /** @param Closure(string): list<array<string, mixed>> $rules */
    public function __construct(
        private readonly Closure $rules,
        private readonly bool $exactlyOne,
    ) {}

    /** @param array<string, mixed> $data */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!Arr::has($this->data, $attribute)) {
            return;
        }

        $data = $this->data;
        Arr::set($data, $attribute, $value);
        $matches = 0;

        foreach (($this->rules)($attribute) as $rules) {
            if (Validator::make($data, $rules)->passes()) {
                $matches++;

                if (!$this->exactlyOne) {
                    return;
                }
            }
        }

        if ($matches !== 1) {
            $fail(
                $this->exactlyOne ? 'phpinnacle-eidos::messages.one_of' : 'phpinnacle-eidos::messages.any_of',
            )->translate();
        }
    }
}

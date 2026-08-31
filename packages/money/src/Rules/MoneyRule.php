<?php

namespace PHPinnacle\Money\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;
use PHPinnacle\Money\Comparison;
use PHPinnacle\Money\Money;

class MoneyRule implements ValidationRule, ValidatorAwareRule
{
    private mixed $value = null;

    public function __construct(
        public readonly Money|string|int $field,
        private readonly Comparison $comparison = Comparison::Equal,
    ) {}

    public static function eq(string $field): self
    {
        return self::make($field, Comparison::Equal);
    }

    public static function gt(string $field): self
    {
        return self::make($field, Comparison::GreaterThan);
    }

    public static function gte(string $field): self
    {
        return self::make($field, Comparison::GreaterThanOrEqual);
    }

    public static function lt(string $field): self
    {
        return self::make($field, Comparison::LessThan);
    }

    public static function lte(string $field): self
    {
        return self::make($field, Comparison::LessThanOrEqual);
    }

    public static function make(string $field, Comparison $comparison): self
    {
        return new self($field, $comparison);
    }

    public static function neq(string $field): self
    {
        return self::make($field, Comparison::NotEqual);
    }

    public function setValidator(Validator $validator): static
    {
        $this->value = is_string($this->field) ? $validator->getValue($this->field) : $this->field;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $this->value === null) {
            return;
        }

        $value = Money::parse($value);
        $other = Money::parse($this->value, $value->currency);

        if (!$this->comparison->satisfy($value, $other)) {
            $fail('phpinnacle-money::validation.money.' . $this->comparison->value)->translate();
        }
    }
}

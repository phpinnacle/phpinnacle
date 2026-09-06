<?php

namespace PHPinnacle\Money\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use PHPinnacle\Money\Comparison;
use PHPinnacle\Money\Money;

class MoneyRule implements ValidationRule, ValidatorAwareRule
{
    private mixed $value = null;

    public function __construct(
        public readonly Money|string|int $field,
        private readonly Comparison $comparison = Comparison::Equal,
    ) {}

    public static function make(Money|string|int $field, Comparison $comparison): self
    {
        return new self($field, $comparison);
    }

    public static function eq(Money|string|int $field): self
    {
        return self::make($field, Comparison::Equal);
    }

    public static function neq(Money|string|int $field): self
    {
        return self::make($field, Comparison::NotEqual);
    }

    public static function gt(Money|string|int $field): self
    {
        return self::make($field, Comparison::GreaterThan);
    }

    public static function gte(Money|string|int $field): self
    {
        return self::make($field, Comparison::GreaterThanOrEqual);
    }

    public static function lt(Money|string|int $field): self
    {
        return self::make($field, Comparison::LessThan);
    }

    public static function lte(Money|string|int $field): self
    {
        return self::make($field, Comparison::LessThanOrEqual);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $this->value === null) {
            return;
        }

        if (!$this->isMoneyInput($value)) {
            $fail('phpinnacle-money::validation.money.invalid')->translate();

            return;
        }

        try {
            $value = Money::parse($value);

            if (!$this->isMoneyInput($this->value, $value->currency)) {
                $fail('phpinnacle-money::validation.money.invalid')->translate();

                return;
            }

            $other = Money::parse($this->value, $value->currency);

            if (!$this->comparison->satisfy($value, $other)) {
                $fail('phpinnacle-money::validation.money.' . $this->comparison->value)->translate();
            }
        } catch (InvalidArgumentException) {
            $fail('phpinnacle-money::validation.money.invalid')->translate();
        }
    }

    private function isMoneyInput(mixed $value, ?string $currency = null): bool
    {
        if ($value instanceof Money) {
            return true;
        }

        if (is_array($value)) {
            if (!array_key_exists('amount', $value)) {
                return false;
            }

            $currency = array_key_exists('currency', $value) ? $value['currency'] : $currency;
            $value = $value['amount'];
        }

        return is_string($currency) && ($value === null || is_int($value) || is_string($value));
    }

    public function setValidator(Validator $validator): static
    {
        $this->value = is_string($this->field) ? $validator->getValue($this->field) : $this->field;

        return $this;
    }
}

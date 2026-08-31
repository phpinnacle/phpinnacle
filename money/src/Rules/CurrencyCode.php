<?php

namespace PHPinnacle\Money\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use PHPinnacle\Money\Currencies;

class CurrencyCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('validation.string')->translate();

            return;
        }

        if (!Currencies::exists($value)) {
            $fail('phpinnacle-money::validation.currency_code')->translate();
        }
    }
}

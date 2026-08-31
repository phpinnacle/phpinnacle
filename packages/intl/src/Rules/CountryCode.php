<?php

namespace PHPinnacle\Intl\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use PHPinnacle\Intl\Countries;

class CountryCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('validation.string')->translate();

            return;
        }

        if (!Countries::exists($value)) {
            $fail('phpinnacle-intl::validation.country_code')->translate();
        }
    }
}

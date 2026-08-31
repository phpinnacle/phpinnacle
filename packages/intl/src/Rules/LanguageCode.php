<?php

namespace PHPinnacle\Intl\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use PHPinnacle\Intl\Languages;

class LanguageCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('validation.string')->translate();

            return;
        }

        if (!Languages::exists(strtolower($value))) {
            $fail('phpinnacle-intl::validation.language_code')->translate();
        }
    }
}

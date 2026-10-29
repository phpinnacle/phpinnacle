<?php

namespace PHPinnacle\Eidos\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** @internal */
final class NullSchema implements ValidationRule
{
    public bool $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== null) {
            $fail('validation.in')->translate();
        }
    }
}

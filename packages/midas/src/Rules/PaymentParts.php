<?php

namespace PHPinnacle\Midas\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

readonly class PaymentParts implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('phpinnacle-midas::validation.payment_parts.format')->translate();

            return;
        }

        if ($value === []) {
            $fail('phpinnacle-midas::validation.payment_parts.empty')->translate();

            return;
        }

        $validator = Validator::make(['parts' => $value], [
            'parts.*' => ['array'],
            'parts.*.value' => ['required', 'integer', 'between:1,100'],
            'parts.*.delay' => ['required', 'integer', 'between:0,365'],
        ]);

        if ($validator->fails()) {
            $fail('phpinnacle-midas::validation.payment_parts.invalid')->translate();

            return;
        }

        $values = array_map(intval(...), array_column($validator->validated()['parts'], 'value'));

        if (array_sum($values) !== 100) {
            $fail('phpinnacle-midas::validation.payment_parts.sum')->translate();
        }
    }
}

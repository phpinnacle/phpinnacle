<?php

namespace PHPinnacle\Eidos\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;
use PHPinnacle\Jason\JsonPath;
use PHPinnacle\Jason\Schema;

/** @internal */
final class ValidPath implements ValidationRule
{
    public bool $implicit = true;

    /** @param Closure(): Schema $schema */
    public function __construct(
        private readonly Closure $schema,
        private readonly bool $selectArrayItemFields = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (!is_string($value)) {
            $fail('validation.string')->translate();

            return;
        }

        try {
            $segments = JsonPath::parse($value);
        } catch (InvalidArgumentException) {
            $fail('validation.in')->translate();

            return;
        }

        if (
            $segments === []
            || array_any($segments, fn ($segment) => is_int($segment))
            || !$this->selectArrayItemFields
            && in_array(null, $segments, true)
            || !($this->schema)()->resolveReferences()->hasDeclaredPath($segments)
        ) {
            $fail('validation.in')->translate();
        }
    }
}

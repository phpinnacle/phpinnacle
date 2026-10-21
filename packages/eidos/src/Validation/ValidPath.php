<?php

namespace PHPinnacle\Eidos\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\JsonSchema\Types\Type;
use PHPinnacle\Eidos\JsonSchema\Deserializer;
use PHPinnacle\Eidos\JsonSchema\Node;

/** @internal */
final class ValidPath implements ValidationRule
{
    public bool $implicit = true;

    /** @param Closure(): Type $schema */
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

        if (
            $value === '$'
            || !new Node(Deserializer::resolveReferences(($this->schema)()->toArray()))->hasPath(
                $value,
                $this->selectArrayItemFields,
            )
        ) {
            $fail('validation.in')->translate();
        }
    }
}

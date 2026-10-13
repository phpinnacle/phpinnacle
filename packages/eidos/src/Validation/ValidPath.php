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
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (!is_array($value)) {
            $fail('validation.array')->translate();

            return;
        }

        if (!array_is_list($value)) {
            $fail('validation.list')->translate();

            return;
        }

        foreach ($value as $segment) {
            if (!is_string($segment)) {
                $fail('validation.string')->translate();

                return;
            }
        }

        if ($value === []) {
            $fail('validation.min.array')->translate(['min' => 1]);

            return;
        }

        if (!new Node(Deserializer::resolveReferences(($this->schema)()->toArray()))->hasPath($value)) {
            $fail('validation.in')->translate();
        }
    }
}

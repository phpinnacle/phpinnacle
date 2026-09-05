<?php

namespace PHPinnacle\Langcat\Response\Groups;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class GradeValueResponse
{
    public function __construct(
        public int|float|string|null $value,
        public ?string $formatted,
        public ?int $teacherId,
        public ?string $gradedAt,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::nullableNumberOrString($payload, 'value'),
            ResponseValue::nullableString($payload, 'formatted'),
            ResponseValue::nullableInt($payload, 'teacherId'),
            ResponseValue::nullableString($payload, 'gradedAt'),
        );
    }
}

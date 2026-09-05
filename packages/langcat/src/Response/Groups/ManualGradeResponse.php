<?php

namespace PHPinnacle\Langcat\Response\Groups;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class ManualGradeResponse
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?int $position,
        public ?string $color,
        public ?int $weight,
        public ?GradeValueResponse $grade,
        public string $createdAt,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $grade = ResponseValue::nullableObject($payload, 'grade');

        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::nullableString($payload, 'name'),
            ResponseValue::nullableInt($payload, 'position'),
            ResponseValue::nullableString($payload, 'color'),
            ResponseValue::nullableInt($payload, 'weight'),
            $grade === null ? null : GradeValueResponse::fromArray($grade),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

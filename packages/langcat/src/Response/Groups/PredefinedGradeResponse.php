<?php

namespace PHPinnacle\Langcat\Response\Groups;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class PredefinedGradeResponse
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?int $weight,
        public ?string $color,
        public int|float|null $maxValue,
        public bool $isShared,
        public string $createdAt,
        public int $collectionId,
        public ?GradeValueResponse $grade,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $grade = ResponseValue::nullableObject($payload, 'grade');

        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::nullableString($payload, 'name'),
            ResponseValue::nullableInt($payload, 'weight'),
            ResponseValue::nullableString($payload, 'color'),
            ResponseValue::nullableNumber($payload, 'maxValue'),
            ResponseValue::bool($payload, 'isShared'),
            ResponseValue::string($payload, 'createdAt'),
            ResponseValue::int($payload, 'collectionId'),
            $grade === null ? null : GradeValueResponse::fromArray($grade),
        );
    }
}

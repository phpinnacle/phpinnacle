<?php

namespace PHPinnacle\Langcat\Response\GroupSettings;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class GradeCollectionResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?int $schoolId,
        public bool $isShared,
        public string $createdAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::nullableInt($payload, 'schoolId'),
            ResponseValue::bool($payload, 'isShared'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

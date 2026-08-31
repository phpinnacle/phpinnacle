<?php

namespace PHPinnacle\Langcat\Response\GroupSettings;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class CourseBookResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public int $languageId,
        public int $levelId,
        public int $schoolId,
        public bool $isShared,
        public bool $isArchived,
        public string $createdAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::nullableString($payload, 'description'),
            ResponseValue::int($payload, 'languageId'),
            ResponseValue::int($payload, 'levelId'),
            ResponseValue::int($payload, 'schoolId'),
            ResponseValue::bool($payload, 'isShared'),
            ResponseValue::bool($payload, 'isArchived'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

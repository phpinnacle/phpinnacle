<?php

namespace PHPinnacle\Langcat\Response\GroupSettings;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class CourseResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $schoolId,
        public int $languageId,
        public int $levelId,
        public int $ageGroupId,
        public int $courseBookId,
        public ?int $programCollectionId,
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
            ResponseValue::int($payload, 'schoolId'),
            ResponseValue::int($payload, 'languageId'),
            ResponseValue::int($payload, 'levelId'),
            ResponseValue::int($payload, 'ageGroupId'),
            ResponseValue::int($payload, 'courseBookId'),
            ResponseValue::nullableInt($payload, 'programCollectionId'),
            ResponseValue::bool($payload, 'isShared'),
            ResponseValue::bool($payload, 'isArchived'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

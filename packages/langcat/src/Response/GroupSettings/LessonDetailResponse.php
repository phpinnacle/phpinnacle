<?php

namespace PHPinnacle\Langcat\Response\GroupSettings;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class LessonDetailResponse
{
    public function __construct(
        public int $id,
        public int $schoolId,
        public bool $isShared,
        public int $position,
        public string $name,
        public string $color,
        public bool $onlyForTeacher,
        public string $createdAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::int($payload, 'schoolId'),
            ResponseValue::bool($payload, 'isShared'),
            ResponseValue::int($payload, 'position'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::string($payload, 'color'),
            ResponseValue::bool($payload, 'onlyForTeacher'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

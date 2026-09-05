<?php

namespace PHPinnacle\Langcat\Response\GroupSettings;

use PHPinnacle\Langcat\Enum\LessonStatusType;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class LessonStatusResponse
{
    public function __construct(
        public int $id,
        public int $schoolId,
        public bool $isShared,
        public string $name,
        public LessonStatusType $type,
        public int $studentPercent,
        public int $teacherPercent,
        public string $color,
        public string $createdAt,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::int($payload, 'schoolId'),
            ResponseValue::bool($payload, 'isShared'),
            ResponseValue::string($payload, 'name'),
            LessonStatusType::from(ResponseValue::string($payload, 'type')),
            ResponseValue::int($payload, 'studentPercent'),
            ResponseValue::int($payload, 'teacherPercent'),
            ResponseValue::string($payload, 'color'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

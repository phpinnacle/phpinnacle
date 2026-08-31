<?php

namespace PHPinnacle\Langcat\Response\GroupSettings;

use PHPinnacle\Langcat\Enum\AttendanceStatusType;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class AttendanceStatusResponse
{
    public function __construct(
        public int $id,
        public int $schoolId,
        public bool $isShared,
        public string $name,
        public string $shortName,
        public AttendanceStatusType $type,
        public int $studentPercent,
        public int $attendancePercent,
        public string $color,
        public string $createdAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::int($payload, 'schoolId'),
            ResponseValue::bool($payload, 'isShared'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::string($payload, 'shortName'),
            AttendanceStatusType::from(ResponseValue::string($payload, 'type')),
            ResponseValue::int($payload, 'studentPercent'),
            ResponseValue::int($payload, 'attendancePercent'),
            ResponseValue::string($payload, 'color'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

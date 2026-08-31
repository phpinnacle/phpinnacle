<?php

namespace PHPinnacle\Langcat\Response\Groups;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class StudentAttendanceResponse
{
    public function __construct(
        public int $studentId,
        public ?int $attendanceStatusId,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'studentId'),
            ResponseValue::nullableInt($payload, 'attendanceStatusId'),
        );
    }
}

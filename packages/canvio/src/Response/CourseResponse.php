<?php

namespace PHPinnacle\Canvio\Response;

use PHPinnacle\Canvio\Support\ResponseValue;

final readonly class CourseResponse
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $courseCode,
        public ?string $workflowState,
        public ?int $accountId,
        public ?int $enrollmentTermId,
        public ?string $uuid,
        public ?string $startAt,
        public ?string $endAt,
        public ?string $timeZone,
        public ?string $sisCourseId,
        public array $raw,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::nullableString($payload, 'course_code'),
            ResponseValue::nullableString($payload, 'workflow_state'),
            ResponseValue::nullableInt($payload, 'account_id'),
            ResponseValue::nullableInt($payload, 'enrollment_term_id'),
            ResponseValue::nullableString($payload, 'uuid'),
            ResponseValue::nullableString($payload, 'start_at'),
            ResponseValue::nullableString($payload, 'end_at'),
            ResponseValue::nullableString($payload, 'time_zone'),
            ResponseValue::nullableString($payload, 'sis_course_id'),
            $payload,
        );
    }
}

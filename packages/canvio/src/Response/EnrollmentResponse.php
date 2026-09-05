<?php

namespace PHPinnacle\Canvio\Response;

use PHPinnacle\Canvio\Support\ResponseValue;

final readonly class EnrollmentResponse
{
    /**
     * @param  array<array-key, mixed>|null  $grades
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public string $type,
        public string $enrollmentState,
        public ?string $role,
        public ?int $userId,
        public ?int $courseId,
        public ?int $courseSectionId,
        public ?string $startAt,
        public ?string $endAt,
        public ?string $lastActivityAt,
        public ?int $totalActivityTime,
        public ?array $grades,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'type'),
            ResponseValue::string($payload, 'enrollment_state'),
            ResponseValue::nullableString($payload, 'role'),
            ResponseValue::nullableInt($payload, 'user_id'),
            ResponseValue::nullableInt($payload, 'course_id'),
            ResponseValue::nullableInt($payload, 'course_section_id'),
            ResponseValue::nullableString($payload, 'start_at'),
            ResponseValue::nullableString($payload, 'end_at'),
            ResponseValue::nullableString($payload, 'last_activity_at'),
            ResponseValue::nullableInt($payload, 'total_activity_time'),
            ResponseValue::nullableObject($payload, 'grades'),
            $payload,
        );
    }
}

<?php

namespace PHPinnacle\Canvio\Response;

use PHPinnacle\Canvio\Support\ResponseValue;

final readonly class AssignmentResponse
{
    /**
     * @param  list<string>  $submissionTypes
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?int $courseId,
        public ?string $description,
        public ?string $dueAt,
        public ?string $unlockAt,
        public ?string $lockAt,
        public int|float|null $pointsPossible,
        public ?string $gradingType,
        public array $submissionTypes,
        public ?bool $published,
        public ?string $htmlUrl,
        public array $raw,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::nullableInt($payload, 'course_id'),
            ResponseValue::nullableString($payload, 'description'),
            ResponseValue::nullableString($payload, 'due_at'),
            ResponseValue::nullableString($payload, 'unlock_at'),
            ResponseValue::nullableString($payload, 'lock_at'),
            ResponseValue::nullableNumber($payload, 'points_possible'),
            ResponseValue::nullableString($payload, 'grading_type'),
            ResponseValue::strings($payload, 'submission_types'),
            ResponseValue::nullableBool($payload, 'published'),
            ResponseValue::nullableString($payload, 'html_url'),
            $payload,
        );
    }
}

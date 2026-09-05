<?php

namespace PHPinnacle\Canvio\Response;

use PHPinnacle\Canvio\Support\ResponseValue;

final readonly class SubmissionResponse
{
    /** @param array<array-key, mixed> $raw */
    public function __construct(
        public int $assignmentId,
        public int $userId,
        public string $workflowState,
        public ?string $submissionType,
        public ?string $submittedAt,
        public int|float|null $score,
        public ?string $grade,
        public ?int $attempt,
        public ?bool $late,
        public ?bool $missing,
        public ?bool $excused,
        public ?string $body,
        public ?string $url,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'assignment_id'),
            ResponseValue::int($payload, 'user_id'),
            ResponseValue::string($payload, 'workflow_state'),
            ResponseValue::nullableString($payload, 'submission_type'),
            ResponseValue::nullableString($payload, 'submitted_at'),
            ResponseValue::nullableNumber($payload, 'score'),
            ResponseValue::nullableString($payload, 'grade'),
            ResponseValue::nullableInt($payload, 'attempt'),
            ResponseValue::nullableBool($payload, 'late'),
            ResponseValue::nullableBool($payload, 'missing'),
            ResponseValue::nullableBool($payload, 'excused'),
            ResponseValue::nullableString($payload, 'body'),
            ResponseValue::nullableString($payload, 'url'),
            $payload,
        );
    }
}

<?php

namespace PHPinnacle\Langcat\Response\Events;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class EventStudentInGroupDetailsResponse
{
    public function __construct(
        public EventStudentDetailsResponse $student,
        public EventGroupDetailsResponse $group,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            EventStudentDetailsResponse::fromArray(ResponseValue::object($payload, 'student')),
            EventGroupDetailsResponse::fromArray(ResponseValue::object($payload, 'group')),
        );
    }
}

<?php

namespace PHPinnacle\Langcat\Response\Groups;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class GroupTeacherSearchResponse
{
    public function __construct(
        public int $id,
        public ?TeacherBasicResponse $basic,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $basic = ResponseValue::nullableObject($payload, 'teachers.basic');

        return new self(
            ResponseValue::int($payload, 'id'),
            $basic === null ? null : TeacherBasicResponse::fromArray($basic),
        );
    }
}

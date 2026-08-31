<?php

namespace PHPinnacle\Langcat\Response\Groups;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class TeacherInGroupResponse
{
    public function __construct(
        public int $id,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(ResponseValue::int($payload, 'id'));
    }
}

<?php

namespace PHPinnacle\Langcat\Response\GroupSettings;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class GroupSettingTeacherResponse
{
    public function __construct(
        public int $id,
        public int|float $salary,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(ResponseValue::int($payload, 'id'), ResponseValue::number($payload, 'salary'));
    }
}

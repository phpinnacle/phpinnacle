<?php

namespace PHPinnacle\Langcat\Response\Groups;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class LessonCheckResponse
{
    public function __construct(
        public bool $isChecked,
        public ?string $checkedAt,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::bool($payload, 'isChecked'),
            ResponseValue::nullableString($payload, 'checkedAt'),
        );
    }
}

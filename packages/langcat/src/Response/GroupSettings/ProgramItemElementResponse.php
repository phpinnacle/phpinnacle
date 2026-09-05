<?php

namespace PHPinnacle\Langcat\Response\GroupSettings;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class ProgramItemElementResponse
{
    public function __construct(
        public int $lessonDetailsId,
        public string $description,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'lessonDetailsId'),
            ResponseValue::string($payload, 'description'),
        );
    }
}

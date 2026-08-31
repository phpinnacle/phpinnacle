<?php

namespace PHPinnacle\Langcat\Response\Shared;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class ErrorResponse
{
    public function __construct(
        public ?string $message,
        public ?string $docs,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::nullableString($payload, 'message'),
            ResponseValue::nullableString($payload, 'docs'),
        );
    }
}

<?php

namespace PHPinnacle\Langcat\Response\Groups;

use PHPinnacle\Langcat\Enum\OnlineLessonProvider;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class OnlineLessonResponse
{
    public function __construct(
        public OnlineLessonProvider $provider,
        public ?string $url,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            OnlineLessonProvider::from(ResponseValue::string($payload, 'provider')),
            ResponseValue::nullableString($payload, 'url'),
        );
    }
}

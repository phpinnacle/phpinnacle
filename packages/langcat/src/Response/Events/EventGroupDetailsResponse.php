<?php

namespace PHPinnacle\Langcat\Response\Events;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class EventGroupDetailsResponse
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'name'),
        );
    }
}

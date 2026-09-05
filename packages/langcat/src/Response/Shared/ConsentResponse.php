<?php

namespace PHPinnacle\Langcat\Response\Shared;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class ConsentResponse
{
    public function __construct(
        public int $id,
        public int $consentId,
        public bool $value,
        public string $createdAt,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::int($payload, 'consentId'),
            ResponseValue::bool($payload, 'value'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

<?php

namespace PHPinnacle\Langcat\Response\Schools;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class SchoolResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $shortName,
        public string $createdAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::string($payload, 'shortName'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

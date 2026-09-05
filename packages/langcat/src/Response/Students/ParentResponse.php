<?php

namespace PHPinnacle\Langcat\Response\Students;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class ParentResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $lastName,
        public bool $isArchived,
        public ?string $archivedAt,
        public string $createdAt,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::string($payload, 'lastName'),
            ResponseValue::bool($payload, 'isArchived'),
            ResponseValue::nullableString($payload, 'archivedAt'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

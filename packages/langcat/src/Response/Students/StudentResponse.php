<?php

namespace PHPinnacle\Langcat\Response\Students;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class StudentResponse
{
    /** @param list<int> $schoolIds */
    public function __construct(
        public int $id,
        public string $name,
        public string $lastName,
        public string $type,
        public array $schoolIds,
        public bool $hasParentAccounts,
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
            ResponseValue::string($payload, 'type'),
            ResponseValue::integers($payload, 'schoolIds'),
            ResponseValue::bool($payload, 'hasParentAccounts'),
            ResponseValue::bool($payload, 'isArchived'),
            ResponseValue::nullableString($payload, 'archivedAt'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

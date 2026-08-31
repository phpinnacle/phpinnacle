<?php

namespace PHPinnacle\Langcat\Response\Students;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class StudentBasicResponse
{
    /** @param list<int>|null $schoolIds */
    public function __construct(
        public string $name,
        public string $lastName,
        public string $type,
        public ?array $schoolIds,
        public ?bool $hasParentAccounts,
        public bool $isArchived,
        public ?string $archivedAt,
        public string $createdAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::string($payload, 'name'),
            ResponseValue::string($payload, 'lastName'),
            ResponseValue::string($payload, 'type'),
            ResponseValue::nullableIntegers($payload, 'schoolIds'),
            ResponseValue::nullableBool($payload, 'hasParentAccounts'),
            ResponseValue::bool($payload, 'isArchived'),
            ResponseValue::nullableString($payload, 'archivedAt'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

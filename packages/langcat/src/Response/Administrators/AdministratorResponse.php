<?php

namespace PHPinnacle\Langcat\Response\Administrators;

use PHPinnacle\Langcat\Enum\UserRole;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class AdministratorResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $lastName,
        public UserRole $role,
        public bool $isArchived,
        public ?string $archivedAt,
        public string $createdAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::string($payload, 'lastName'),
            UserRole::from(ResponseValue::string($payload, 'role')),
            ResponseValue::bool($payload, 'isArchived'),
            ResponseValue::nullableString($payload, 'archivedAt'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

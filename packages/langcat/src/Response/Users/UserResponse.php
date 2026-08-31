<?php

namespace PHPinnacle\Langcat\Response\Users;

use PHPinnacle\Langcat\Enum\UserRole;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class UserResponse
{
    public function __construct(
        public int $id,
        public UserRole $role,
        public string $name,
        public string $lastName,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            UserRole::from(ResponseValue::string($payload, 'role')),
            ResponseValue::string($payload, 'name'),
            ResponseValue::string($payload, 'lastName'),
        );
    }
}

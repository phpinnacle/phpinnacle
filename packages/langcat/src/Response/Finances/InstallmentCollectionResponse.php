<?php

namespace PHPinnacle\Langcat\Response\Finances;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class InstallmentCollectionResponse
{
    public function __construct(
        public int $id,
        public int $schoolId,
        public string $name,
        public bool $isShared,
        public string $createdAt,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::int($payload, 'schoolId'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::bool($payload, 'isShared'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

<?php

namespace PHPinnacle\Langcat\Response\Finances;

use PHPinnacle\Langcat\Enum\InstallmentTagType;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class InstallmentTagResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public InstallmentTagType $type,
        public bool $visibleToStudent,
        public string $createdAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'name'),
            InstallmentTagType::from(ResponseValue::string($payload, 'type')),
            ResponseValue::bool($payload, 'visibleToStudent'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

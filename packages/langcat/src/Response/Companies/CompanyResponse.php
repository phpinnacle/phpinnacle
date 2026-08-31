<?php

namespace PHPinnacle\Langcat\Response\Companies;

use PHPinnacle\Langcat\Enum\BillingModel;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class CompanyResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $shortName,
        public BillingModel $billingModel,
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
            ResponseValue::string($payload, 'shortName'),
            BillingModel::from(ResponseValue::string($payload, 'billingModel')),
            ResponseValue::bool($payload, 'isArchived'),
            ResponseValue::nullableString($payload, 'archivedAt'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}

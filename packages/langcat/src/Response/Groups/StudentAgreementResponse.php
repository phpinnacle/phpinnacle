<?php

namespace PHPinnacle\Langcat\Response\Groups;

use PHPinnacle\Langcat\Enum\BillingModel;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class StudentAgreementResponse
{
    public function __construct(
        public ?string $dueDate,
        public ?string $signedDate,
        public BillingModel $billingModel,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::nullableString($payload, 'dueDate'),
            ResponseValue::nullableString($payload, 'signedDate'),
            BillingModel::from(ResponseValue::string($payload, 'billingModel')),
        );
    }
}

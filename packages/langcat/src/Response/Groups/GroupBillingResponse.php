<?php

namespace PHPinnacle\Langcat\Response\Groups;

use PHPinnacle\Langcat\Enum\BillingCalculationBase;
use PHPinnacle\Langcat\Enum\BillingModel;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class GroupBillingResponse
{
    public function __construct(
        public BillingModel $model,
        public ?BillingCalculationBase $calculationBase,
        public ?string $price,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $base = ResponseValue::nullableString($payload, 'calculationBase');

        return new self(
            BillingModel::from(ResponseValue::string($payload, 'model')),
            $base === null ? null : BillingCalculationBase::from($base),
            ResponseValue::nullableString($payload, 'price'),
        );
    }
}

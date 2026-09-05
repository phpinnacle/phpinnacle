<?php

namespace PHPinnacle\Langcat\Response\Shared;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class DetailsResponse
{
    public function __construct(
        public ?PersonalDetailsResponse $personal,
        public ?AddressDetailsResponse $address,
        public ?BillingDetailsResponse $billing,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        if (
            !array_key_exists('personal', $payload)
            && !array_key_exists('address', $payload)
            && !array_key_exists('billing', $payload)
        ) {
            return new self(PersonalDetailsResponse::fromArray($payload), null, null);
        }

        $personal = ResponseValue::nullableObject($payload, 'personal');
        $address = ResponseValue::nullableObject($payload, 'address');
        $billing = ResponseValue::nullableObject($payload, 'billing');

        return new self(
            $personal === null ? null : PersonalDetailsResponse::fromArray($personal),
            $address === null ? null : AddressDetailsResponse::fromArray($address),
            $billing === null ? null : BillingDetailsResponse::fromArray($billing),
        );
    }
}

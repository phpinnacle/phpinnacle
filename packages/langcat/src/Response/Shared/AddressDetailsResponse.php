<?php

namespace PHPinnacle\Langcat\Response\Shared;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class AddressDetailsResponse
{
    public function __construct(
        public ?string $street,
        public ?string $zipCode,
        public ?string $city,
        public ?string $country,
        public ?string $province,
        public ?string $post,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::nullableString($payload, 'street'),
            ResponseValue::nullableString($payload, 'zipCode'),
            ResponseValue::nullableString($payload, 'city'),
            ResponseValue::nullableString($payload, 'country'),
            ResponseValue::nullableString($payload, 'province'),
            ResponseValue::nullableString($payload, 'post'),
        );
    }
}

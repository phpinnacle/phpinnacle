<?php

namespace PHPinnacle\Langcat\Response\Shared;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class BillingDetailsResponse
{
    public function __construct(
        public ?string $name,
        public ?string $street,
        public ?string $city,
        public ?string $zipCode,
        public ?string $nip,
        public ?string $pesel,
        public ?string $email,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::nullableString($payload, 'name'),
            ResponseValue::nullableString($payload, 'street'),
            ResponseValue::nullableString($payload, 'city'),
            ResponseValue::nullableString($payload, 'zipCode'),
            ResponseValue::nullableString($payload, 'nip'),
            ResponseValue::nullableString($payload, 'pesel'),
            ResponseValue::nullableString($payload, 'email'),
        );
    }
}

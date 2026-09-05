<?php

namespace PHPinnacle\Langcat\Response\Shared;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class PersonalDetailsResponse
{
    public function __construct(
        public ?string $phone,
        public ?string $mobile,
        public ?string $email,
        public ?string $skype,
        public ?string $pesel,
        public ?string $nip,
        public ?string $birthDate,
        public ?string $birthPlace,
        public ?string $description,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::nullableString($payload, 'phone'),
            ResponseValue::nullableString($payload, 'mobile'),
            ResponseValue::nullableString($payload, 'email'),
            ResponseValue::nullableString($payload, 'skype'),
            ResponseValue::nullableString($payload, 'pesel'),
            ResponseValue::nullableString($payload, 'nip'),
            ResponseValue::nullableString($payload, 'birthDate'),
            ResponseValue::nullableString($payload, 'birthPlace'),
            ResponseValue::nullableString($payload, 'description'),
        );
    }
}

<?php

namespace PHPinnacle\Canvio\Response;

use PHPinnacle\Canvio\Support\ResponseValue;

final readonly class UserResponse
{
    /** @param array<array-key, mixed> $raw */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $sortableName,
        public ?string $shortName,
        public ?string $sisUserId,
        public ?string $integrationId,
        public ?string $loginId,
        public ?string $avatarUrl,
        public ?string $email,
        public ?string $locale,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::nullableString($payload, 'sortable_name'),
            ResponseValue::nullableString($payload, 'short_name'),
            ResponseValue::nullableString($payload, 'sis_user_id'),
            ResponseValue::nullableString($payload, 'integration_id'),
            ResponseValue::nullableString($payload, 'login_id'),
            ResponseValue::nullableString($payload, 'avatar_url'),
            ResponseValue::nullableString($payload, 'email'),
            ResponseValue::nullableString($payload, 'locale'),
            $payload,
        );
    }
}

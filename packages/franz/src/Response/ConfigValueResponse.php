<?php

namespace PHPinnacle\Franz\Response;

use PHPinnacle\Franz\Support\ResponseValue;

final readonly class ConfigValueResponse
{
    /**
     * @param list<string> $recommendedValues
     * @param list<string> $errors
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public string $name,
        public ?string $value,
        public array $recommendedValues,
        public array $errors,
        public bool $visible,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::string($payload['name'] ?? null),
            ResponseValue::string($payload['value'] ?? null, nullable: true),
            ResponseValue::strings($payload['recommended_values'] ?? null),
            ResponseValue::strings($payload['errors'] ?? null),
            ResponseValue::bool($payload['visible'] ?? null),
            $payload,
        );
    }
}

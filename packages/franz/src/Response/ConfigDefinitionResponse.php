<?php

namespace PHPinnacle\Franz\Response;

use PHPinnacle\Franz\Support\ResponseValue;

final readonly class ConfigDefinitionResponse
{
    /**
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public string $name,
        public string $type,
        public bool $required,
        public ?string $defaultValue,
        public string $importance,
        public string $documentation,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::string($payload['name'] ?? null),
            ResponseValue::string($payload['type'] ?? null),
            ResponseValue::bool($payload['required'] ?? null),
            ResponseValue::string($payload['default_value'] ?? null, nullable: true),
            ResponseValue::string($payload['importance'] ?? null),
            ResponseValue::string($payload['documentation'] ?? null),
            $payload,
        );
    }
}

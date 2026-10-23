<?php

namespace PHPinnacle\Franz\Response;

use PHPinnacle\Franz\Support\ResponseValue;

final readonly class PluginResponse
{
    /**
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public string $class,
        public string $type,
        public ?string $version,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::string($payload['class'] ?? null),
            ResponseValue::string($payload['type'] ?? null),
            ($payload['version'] ?? null) === null ? null : ResponseValue::string($payload['version']),
            $payload,
        );
    }
}

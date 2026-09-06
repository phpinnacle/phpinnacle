<?php

namespace PHPinnacle\Franz\Response;

use PHPinnacle\Franz\Support\ResponseValue;

final readonly class LoggerResponse
{
    /** @param array<array-key, mixed> $raw */
    public function __construct(
        public string $level,
        public ?int $lastModified,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::string($payload['level'] ?? null),
            ($payload['last_modified'] ?? null) !== null ? ResponseValue::int($payload['last_modified']) : null,
            $payload,
        );
    }
}

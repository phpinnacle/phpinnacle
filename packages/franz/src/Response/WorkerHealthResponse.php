<?php

namespace PHPinnacle\Franz\Response;

use PHPinnacle\Franz\Support\ResponseValue;

final readonly class WorkerHealthResponse
{
    /** @param array<array-key, mixed> $raw */
    public function __construct(
        public string $status,
        public string $message,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::string($payload['status'] ?? null),
            ResponseValue::string($payload['message'] ?? null),
            $payload,
        );
    }
}

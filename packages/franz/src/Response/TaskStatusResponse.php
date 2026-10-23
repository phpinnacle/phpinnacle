<?php

namespace PHPinnacle\Franz\Response;

use PHPinnacle\Franz\Support\ResponseValue;

final readonly class TaskStatusResponse
{
    /**
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public int $id,
        public string $state,
        public string $workerId,
        public ?string $trace,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload['id'] ?? null),
            ResponseValue::string($payload['state'] ?? null),
            ResponseValue::string($payload['worker_id'] ?? null),
            ResponseValue::string($payload['trace'] ?? null, nullable: true),
            $payload,
        );
    }
}

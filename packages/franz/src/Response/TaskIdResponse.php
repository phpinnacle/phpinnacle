<?php

namespace PHPinnacle\Franz\Response;

use PHPinnacle\Franz\Support\ResponseValue;

final readonly class TaskIdResponse
{
    /**
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public string $connector,
        public int $task,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::string($payload['connector'] ?? null),
            ResponseValue::int($payload['task'] ?? null),
            $payload,
        );
    }
}
